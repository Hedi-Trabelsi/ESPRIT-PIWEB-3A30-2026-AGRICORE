<?php

namespace App\Controller;

use App\Entity\Depense;
use App\Entity\Vente;
use App\Form\DepenseType;
use App\Form\VenteType;
use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use App\Repository\DepenseRepository;
use App\Service\AnomalyService;
use App\Service\EmailService;
use App\Service\ForecastService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FinanceController extends AbstractController
{
    // ===================== BACKEND =====================

    #[Route('/back/ventes-depenses', name: 'back_ventes_depenses')]
    public function ventesDepenses(Request $request, UserRepository $userRepository, VenteRepository $venteRepository, DepenseRepository $depenseRepository): Response
    {
        $searchTerm = $request->query->get('q');
        $filterRole = $request->query->get('role');

        $queryBuilder = $userRepository->createQueryBuilder('u');

        if ($searchTerm) {
            $queryBuilder->andWhere('u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $searchTerm . '%');
        }

        if ($filterRole !== null && $filterRole !== '') {
            $queryBuilder->andWhere('u.role = :role')
                ->setParameter('role', (int)$filterRole);
        }

        $users = $queryBuilder->getQuery()->getResult();

        $totalUsers = count($users);
        $totalExpensesAll = 0;

        $usersWithStats = [];
        foreach ($users as $user) {
            $userTotalExpenses = 0;
            foreach ($user->getDepenses() as $depense) {
                $userTotalExpenses += $depense->getMontant();
            }

            $userTotalVentes = 0;
            foreach ($user->getVentes() as $vente) {
                $userTotalVentes += $vente->getChiffreAffaires();
            }

            $usersWithStats[] = [
                'user' => $user,
                'totalExpenses' => $userTotalExpenses,
                'totalVentes' => $userTotalVentes,
                'grandTotal' => $userTotalVentes - $userTotalExpenses
            ];

            $totalExpensesAll += $userTotalExpenses;
        }

        return $this->render('back/ventes_depenses/ventes_depenses.html.twig', [
            'usersWithStats' => $usersWithStats,
            'totalUsers' => $totalUsers,
            'totalExpenses' => $totalExpensesAll,
            'searchTerm' => $searchTerm,
            'filterRole' => $filterRole
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/details', name: 'back_user_details')]
    public function userDetails(int $id, UserRepository $userRepository, AnomalyService $anomalyService, ForecastService $forecastService): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $depenses = $user->getDepenses()->toArray();
        $ventes = $user->getVentes()->toArray();

        $totalExpenses = 0.0;
        foreach ($depenses as $d) $totalExpenses += $d->getMontant();
        $totalSales = 0.0;
        foreach ($ventes as $v) $totalSales += $v->getChiffreAffaires();

        $anomalyResults = $anomalyService->analyzeAll($depenses);
        $anomaliesCount = count(array_filter($anomalyResults, fn($r) => $r['analysis']['isAnomaly']));
        $anomalyRate = count($anomalyResults) > 0 ? ($anomaliesCount / count($anomalyResults)) * 100 : 0;
        $forecastData = $forecastService->forecastUserSales($ventes);

        return $this->render('back/ventes_depenses/vente_depense_details.html.twig', [
            'user' => $user,
            'ventes' => $user->getVentes(),
            'depenses' => $user->getDepenses(),
            'totalExpenses' => $totalExpenses,
            'totalSales' => $totalSales,
            'netProfit' => $totalSales - $totalExpenses,
            'anomalyResults' => $anomalyResults,
            'anomaliesCount' => $anomaliesCount,
            'anomalyRate' => $anomalyRate,
            'forecastData' => $forecastData,
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/outils', name: 'back_user_outils')]
    public function userOutils(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        return $this->render('back/ventes_depenses/vente_depense_outils.html.twig', [
            'user' => $user,
            'ventes' => $user->getVentes(),
            'depenses' => $user->getDepenses(),
        ]);
    }

    #[Route('/back/ventes-depenses/{id}/add-depense', name: 'back_add_depense')]
    public function addDepense(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em, AnomalyService $anomalyService, EmailService $emailService): Response
    {
        $user = $userRepository->find($id);
        if (!$user) throw $this->createNotFoundException('User not found');

        $depense = new Depense();
        $depense->setUser($user);
        $form = $this->createForm(DepenseType::class, $depense);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $history = $user->getDepenses()->toArray();
            $em->persist($depense);
            $em->flush();

            $analysis = $anomalyService->analyzeDepense($history, $depense);
            if ($analysis['isAnomaly']) {
                if ($emailService->sendAnomalyAlert($user, $depense, $analysis)) {
                    $this->addFlash('warning', 'Dépense enregistrée. Anomalie détectée — alerte envoyée à ' . $user->getEmail() . '.');
                } else {
                    $this->addFlash('error', 'Dépense enregistrée. Anomalie détectée mais envoi de l\'alerte impossible.');
                }
            }

            return $this->redirectToRoute('back_user_outils', ['id' => $id]);
        }

        return $this->render('back/ventes_depenses/add_depense.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'isEdit' => false
        ]);
    }

    #[Route('/back/depense/{id}/edit', name: 'back_edit_depense')]
    public function editDepense(int $id, Request $request, DepenseRepository $depenseRepository, EntityManagerInterface $em, AnomalyService $anomalyService, EmailService $emailService): Response
    {
        $depense = $depenseRepository->find($id);
        if (!$depense) throw $this->createNotFoundException('Dépense non trouvée');

        $user = $depense->getUser();
        $form = $this->createForm(DepenseType::class, $depense);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $history = array_filter(
                $user->getDepenses()->toArray(),
                fn(Depense $d) => $d->getIdDepense() !== $depense->getIdDepense()
            );
            $analysis = $anomalyService->analyzeDepense(array_values($history), $depense);
            if ($analysis['isAnomaly']) {
                if ($emailService->sendAnomalyAlert($user, $depense, $analysis)) {
                    $this->addFlash('warning', 'Dépense modifiée. Anomalie détectée — alerte envoyée à ' . $user->getEmail() . '.');
                } else {
                    $this->addFlash('error', 'Dépense modifiée. Anomalie détectée mais envoi de l\'alerte impossible.');
                }
            }

            return $this->redirectToRoute('back_user_outils', ['id' => $user->getId()]);
        }

        return $this->render('back/ventes_depenses/update_depense.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/back/depense/{id}/delete', name: 'back_delete_depense', methods: ['POST', 'GET'])]
    public function deleteDepense(int $id, DepenseRepository $depenseRepository, EntityManagerInterface $em): Response
    {
        $depense = $depenseRepository->find($id);
        if (!$depense) throw $this->createNotFoundException('Dépense non trouvée');

        $userId = $depense->getUser()->getId();
        $em->remove($depense);
        $em->flush();

        return $this->redirectToRoute('back_user_outils', ['id' => $userId]);
    }

    #[Route('/back/ventes-depenses/{id}/add-vente', name: 'back_add_vente')]
    public function addVente(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = $userRepository->find($id);
        if (!$user) throw $this->createNotFoundException('User not found');

        $vente = new Vente();
        $vente->setUser($user);
        $form = $this->createForm(VenteType::class, $vente);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vente->setChiffreAffaires($vente->getPrixUnitaire() * $vente->getQuantite());
            $em->persist($vente);
            $em->flush();
            return $this->redirectToRoute('back_user_outils', ['id' => $id]);
        }

        return $this->render('back/ventes_depenses/add_vente.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
            'isEdit' => false
        ]);
    }

    #[Route('/back/vente/{id}/edit', name: 'back_edit_vente')]
    public function editVente(int $id, Request $request, VenteRepository $venteRepository, EntityManagerInterface $em): Response
    {
        $vente = $venteRepository->find($id);
        if (!$vente) throw $this->createNotFoundException('Vente non trouvée');

        $user = $vente->getUser();
        $form = $this->createForm(VenteType::class, $vente);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $vente->setChiffreAffaires($vente->getPrixUnitaire() * $vente->getQuantite());
            $em->flush();
            return $this->redirectToRoute('back_user_outils', ['id' => $user->getId()]);
        }

        return $this->render('back/ventes_depenses/update_vente.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/back/vente/{id}/delete', name: 'back_delete_vente', methods: ['POST', 'GET'])]
    public function deleteVente(int $id, VenteRepository $venteRepository, EntityManagerInterface $em): Response
    {
        $vente = $venteRepository->find($id);
        if (!$vente) throw $this->createNotFoundException('Vente non trouvée');

        $userId = $vente->getUser()->getId();
        $em->remove($vente);
        $em->flush();

        return $this->redirectToRoute('back_user_outils', ['id' => $userId]);
    }

    // ===================== FRONTEND =====================

    #[Route('/ventes-depenses', name: 'app_ventes_depenses')]
    public function ventesDepensesFront(Request $request, UserRepository $userRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if ($sessionUser) {
            return $this->redirectToRoute('app_my_details');
        }

        return $this->redirectToRoute('front_login');
    }

    #[Route('/mes-details', name: 'app_my_details')]
    #[Route('/ventes-depenses/user/{id}', name: 'app_user_details_front')]
    public function userDetailsFront(Request $request, UserRepository $userRepository, AnomalyService $anomalyService, ForecastService $forecastService, ?int $id = null): Response
    {
        if ($id === null) {
            $sessionUser = $request->getSession()->get('user');
            if (!$sessionUser) {
                return $this->redirectToRoute('front_login');
            }
            $user = $userRepository->find($sessionUser->getId());
        } else {
            $user = $userRepository->find($id);
        }

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $expensesByCategory = [];
        foreach ($user->getDepenses() as $depense) {
            $category = $depense->getType();
            if (!isset($expensesByCategory[$category])) {
                $expensesByCategory[$category] = 0;
            }
            $expensesByCategory[$category] += $depense->getMontant();
        }

        $monthlyStats = [];
        foreach ($user->getDepenses() as $depense) {
            $month = $depense->getDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['expenses' => 0, 'sales' => 0];
            }
            $monthlyStats[$month]['expenses'] += $depense->getMontant();
        }
        foreach ($user->getVentes() as $vente) {
            $month = $vente->getDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['expenses' => 0, 'sales' => 0];
            }
            $monthlyStats[$month]['sales'] += $vente->getChiffreAffaires();
        }
        ksort($monthlyStats);

        $depensesArr = $user->getDepenses()->toArray();
        $ventesArr = $user->getVentes()->toArray();

        $totalExpenses = 0.0;
        foreach ($depensesArr as $d) $totalExpenses += $d->getMontant();
        $totalSales = 0.0;
        foreach ($ventesArr as $v) $totalSales += $v->getChiffreAffaires();

        $anomalyResults = $anomalyService->analyzeAll($depensesArr);
        $anomaliesCount = count(array_filter($anomalyResults, fn($r) => $r['analysis']['isAnomaly']));
        $maxZScore = 0.0;
        foreach ($anomalyResults as $r) {
            if ($r['analysis']['score'] > $maxZScore) $maxZScore = $r['analysis']['score'];
        }
        $anomalyRate = count($anomalyResults) > 0 ? ($anomaliesCount / count($anomalyResults)) * 100 : 0;
        $forecastData = $forecastService->forecastUserSales($ventesArr);

        return $this->render('front/ventes_depenses/user_details.html.twig', [
            'user' => $user,
            'depenses' => $user->getDepenses(),
            'ventes' => $user->getVentes(),
            'expensesByCategory' => $expensesByCategory,
            'monthlyStats' => $monthlyStats,
            'totalExpenses' => $totalExpenses,
            'totalSales' => $totalSales,
            'netProfit' => $totalSales - $totalExpenses,
            'anomalyResults' => $anomalyResults,
            'anomaliesCount' => $anomaliesCount,
            'maxZScore' => $maxZScore,
            'anomalyRate' => $anomalyRate,
            'forecastData' => $forecastData,
        ]);
    }

    // ===================== BACKEND: AI ANALYSIS DASHBOARD =====================

    #[Route('/back/ventes-depenses/{id}/analyse', name: 'back_user_analyse')]
    public function userAnalyse(int $id, UserRepository $userRepository, AnomalyService $anomalyService, ForecastService $forecastService): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $depensesArr = $user->getDepenses()->toArray();
        $ventesArr = $user->getVentes()->toArray();

        $expensesByCategory = [];
        $totalExpenses = 0.0;
        foreach ($depensesArr as $depense) {
            $category = $depense->getType();
            $expensesByCategory[$category] = ($expensesByCategory[$category] ?? 0) + $depense->getMontant();
            $totalExpenses += $depense->getMontant();
        }

        $monthlyStats = [];
        $totalSales = 0.0;
        foreach ($depensesArr as $depense) {
            $month = $depense->getDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['expenses' => 0, 'sales' => 0];
            }
            $monthlyStats[$month]['expenses'] += $depense->getMontant();
        }
        foreach ($ventesArr as $vente) {
            $month = $vente->getDate()->format('Y-m');
            if (!isset($monthlyStats[$month])) {
                $monthlyStats[$month] = ['expenses' => 0, 'sales' => 0];
            }
            $monthlyStats[$month]['sales'] += $vente->getChiffreAffaires();
            $totalSales += $vente->getChiffreAffaires();
        }
        ksort($monthlyStats);

        $anomalyResults = $anomalyService->analyzeAll($depensesArr);
        $anomaliesCount = count(array_filter($anomalyResults, fn($r) => $r['analysis']['isAnomaly']));
        $maxZScore = 0.0;
        foreach ($anomalyResults as $r) {
            if ($r['analysis']['score'] > $maxZScore) $maxZScore = $r['analysis']['score'];
        }
        $anomalyRate = count($anomalyResults) > 0 ? ($anomaliesCount / count($anomalyResults)) * 100 : 0;
        $forecastData = $forecastService->forecastUserSales($ventesArr);

        return $this->render('back/ventes_depenses/vente_depense_analyse.html.twig', [
            'user' => $user,
            'expensesByCategory' => $expensesByCategory,
            'monthlyStats' => $monthlyStats,
            'totalExpenses' => $totalExpenses,
            'totalSales' => $totalSales,
            'netProfit' => $totalSales - $totalExpenses,
            'anomalyResults' => $anomalyResults,
            'anomaliesCount' => $anomaliesCount,
            'maxZScore' => $maxZScore,
            'anomalyRate' => $anomalyRate,
            'forecastData' => $forecastData,
        ]);
    }

    // ===================== FRONTEND: PDF MONTHLY REPORT =====================

    #[Route('/ventes-depenses/user/{id}/report', name: 'app_user_report_pdf')]
    public function generateReport(int $id, UserRepository $userRepository, ForecastService $forecastService): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        $ventesArr = $user->getVentes()->toArray();
        $depensesArr = $user->getDepenses()->toArray();

        $totalSales = 0.0;
        foreach ($ventesArr as $v) $totalSales += $v->getChiffreAffaires();
        $totalExpenses = 0.0;
        foreach ($depensesArr as $d) $totalExpenses += $d->getMontant();

        $forecastData = $forecastService->forecastUserSales($ventesArr);

        $html = $this->renderView('front/ventes_depenses/report_pdf.html.twig', [
            'user' => $user,
            'ventes' => $ventesArr,
            'depenses' => $depensesArr,
            'totalSales' => $totalSales,
            'totalExpenses' => $totalExpenses,
            'netProfit' => $totalSales - $totalExpenses,
            'forecastData' => $forecastData,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = sprintf('rapport_mensuel_%s_%s.pdf', $user->getNom(), date('m_Y'));

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    // ===================== BACKEND: MANUAL ANOMALY ALERT =====================

    #[Route('/back/depense/{id}/send-alert', name: 'back_send_anomaly_alert')]
    public function sendManualAlert(int $id, DepenseRepository $depenseRepository, AnomalyService $anomalyService, EmailService $emailService): Response
    {
        $depense = $depenseRepository->find($id);
        if (!$depense) throw $this->createNotFoundException('Dépense non trouvée');

        $user = $depense->getUser();
        $history = array_filter(
            $user->getDepenses()->toArray(),
            fn(Depense $d) => $d->getIdDepense() !== $depense->getIdDepense()
        );
        $analysis = $anomalyService->analyzeDepense(array_values($history), $depense);

        if ($emailService->sendAnomalyAlert($user, $depense, $analysis)) {
            $this->addFlash('success', 'Alerte envoyée à ' . $user->getEmail() . '.');
        } else {
            $this->addFlash('error', 'Envoi de l\'alerte échoué : ' . ($emailService->lastError ?: 'erreur inconnue'));
        }

        return $this->redirectToRoute('back_user_details', ['id' => $user->getId()]);
    }

    // ===================== JSON API =====================

    #[Route('/api/finance/user/{id}/summary', name: 'api_finance_user_summary', methods: ['GET'])]
    public function apiUserSummary(int $id, UserRepository $userRepository, AnomalyService $anomalyService, ForecastService $forecastService): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) return new JsonResponse(['error' => 'User not found'], 404);

        $depenses = $user->getDepenses()->toArray();
        $ventes = $user->getVentes()->toArray();

        $totalExpenses = 0.0;
        foreach ($depenses as $d) $totalExpenses += $d->getMontant();
        $totalSales = 0.0;
        foreach ($ventes as $v) $totalSales += $v->getChiffreAffaires();

        $anomalyResults = $anomalyService->analyzeAll($depenses);
        $anomaliesCount = count(array_filter($anomalyResults, fn($r) => $r['analysis']['isAnomaly']));
        $forecastData = $forecastService->forecastUserSales($ventes);

        return new JsonResponse([
            'user' => [
                'id' => $user->getId(),
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
            ],
            'totals' => [
                'sales' => $totalSales,
                'expenses' => $totalExpenses,
                'netProfit' => $totalSales - $totalExpenses,
                'ventesCount' => count($ventes),
                'depensesCount' => count($depenses),
            ],
            'anomalies' => [
                'count' => $anomaliesCount,
                'rate' => count($depenses) > 0 ? ($anomaliesCount / count($depenses)) * 100 : 0,
            ],
            'forecast' => [
                'nextMonth' => $forecastData['nextMonthValue'],
                'advice' => $forecastData['advice'],
                'alerts' => $forecastData['alerts'],
                'horizon' => $forecastData['forecast'],
            ],
        ]);
    }

    #[Route('/api/finance/user/{id}/calendar-events', name: 'api_finance_calendar_events', methods: ['GET'])]
    public function apiCalendarEvents(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) return new JsonResponse(['error' => 'User not found'], 404);

        $events = [];

        foreach ($user->getVentes() as $vente) {
            if (!$vente->getDate()) continue;
            $events[] = [
                'id' => 'v-' . $vente->getIdVente(),
                'title' => 'Vente: ' . $vente->getProduit() . ' (' . $vente->getChiffreAffaires() . ' DT)',
                'start' => $vente->getDate()->format('Y-m-d'),
                'backgroundColor' => '#38a169',
                'borderColor' => '#38a169',
                'textColor' => '#ffffff',
            ];
        }

        foreach ($user->getDepenses() as $depense) {
            if (!$depense->getDate()) continue;
            $events[] = [
                'id' => 'd-' . $depense->getIdDepense(),
                'title' => 'Dépense: ' . $depense->getType() . ' (' . $depense->getMontant() . ' DT)',
                'start' => $depense->getDate()->format('Y-m-d'),
                'backgroundColor' => '#e53e3e',
                'borderColor' => '#e53e3e',
                'textColor' => '#ffffff',
            ];
        }

        return new JsonResponse($events);
    }
}
