<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\UserRepository;
use App\Repository\VenteRepository;
use App\Repository\DepenseRepository;
use App\Service\AnomalyService; 
use App\Service\ForecastService;

use Dompdf\Dompdf;
use Dompdf\Options;

use Symfony\Component\HttpFoundation\JsonResponse;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->render('front/home/index.html.twig');
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('front/home/about.html.twig');
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('front/home/contact.html.twig');
    }

    #[Route('/maintenance', name: 'app_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('front/maintenance/maintenance.html.twig');
    }

    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response
    {
        return $this->render('front/evenements/evenements.html.twig');
    }

    #[Route('/suivi-animal', name: 'app_suivi_animal')]
    public function suiviAnimal(): Response
    {
        return $this->render('front/suivi_animal/suivi_animal.html.twig');
    }

    #[Route('/achat-equipement', name: 'app_achat_equipement')]
    public function achatEquipement(): Response
    {
        return $this->render('front/achat_equipement/achat_equipement.html.twig');
    }

    #[Route('/ventes-depenses', name: 'app_ventes_depenses')]
    public function ventesDepenses(Request $request, UserRepository $userRepository): Response
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
        }

        return $this->render('front/ventes_depenses/ventes_depenses.html.twig', [
            'usersWithStats' => $usersWithStats,
            'searchTerm' => $searchTerm,
            'filterRole' => $filterRole
        ]);
    }

    #[Route('/ventes-depenses/user/{id}', name: 'app_user_details_front')]
    #[Route('/mes-details', name: 'app_my_details')]
    public function userDetails(int $id = null, UserRepository $userRepository, AnomalyService $anomalyService, ForecastService $forecastService): Response
    {
        if ($id === null) {
            $user = $this->getUser();
            if (!$user) {
                return $this->redirectToRoute('front_login');
            }
        } else {
            $user = $userRepository->find($id);
        }

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Data for Pie Chart (Expenses by Category)
        $expensesByCategory = [];
        foreach ($user->getDepenses() as $depense) {
            $category = $depense->getType();
            if (!isset($expensesByCategory[$category])) {
                $expensesByCategory[$category] = 0;
            }
            $expensesByCategory[$category] += $depense->getMontant();
        }

        // Data for Bar Chart (Expenses vs Sales by Month)
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

        // Totals for KPIs
        $totalExpenses = 0;
        foreach ($user->getDepenses() as $depense) {
            $totalExpenses += $depense->getMontant();
        }
        $totalSales = 0;
        foreach ($user->getVentes() as $vente) {
            $totalSales += $vente->getChiffreAffaires();
        }
        $netProfit = $totalSales - $totalExpenses;

        // Anomalies Analysis
        $allDepenses = $user->getDepenses()->toArray();
        $anomalyResults = $anomalyService->analyzeAll($allDepenses);

        $anomaliesCount = 0;
        $maxZScore = 0;
        foreach ($anomalyResults as $res) {
            if ($res['analysis']['isAnomaly']) {
                $anomaliesCount++;
            }
            if ($res['analysis']['score'] > $maxZScore) {
                $maxZScore = $res['analysis']['score'];
            }
        }
        $anomalyRate = count($allDepenses) > 0 ? ($anomaliesCount / count($allDepenses)) * 100 : 0;

        // Forecasting Analysis
        $ventes = $user->getVentes()->toArray();
        $forecastData = $forecastService->forecastUserSales($ventes);

        return $this->render('front/ventes_depenses/user_details.html.twig', [
            'user' => $user,
            'depenses' => $user->getDepenses(),
            'ventes' => $user->getVentes(),
            'totalExpenses' => $totalExpenses,
            'totalSales' => $totalSales,
            'netProfit' => $netProfit,
            'expensesByCategory' => $expensesByCategory,
            'monthlyStats' => $monthlyStats,
            'anomalyResults' => $anomalyResults,
            'anomaliesCount' => $anomaliesCount,
            'maxZScore' => $maxZScore,
            'anomalyRate' => $anomalyRate,
            'forecastData' => $forecastData
        ]);
    }

    #[Route('/ventes-depenses/user/{id}/report', name: 'app_user_report_pdf')]
    public function generateReport(int $id, UserRepository $userRepository, ForecastService $forecastService): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé');
        }

        // Calculation of totals
        $totalExpenses = 0;
        foreach ($user->getDepenses() as $depense) {
            $totalExpenses += $depense->getMontant();
        }
        $totalSales = 0;
        foreach ($user->getVentes() as $vente) {
            $totalSales += $vente->getChiffreAffaires();
        }
        $netProfit = $totalSales - $totalExpenses;

        // Forecast Data (AI Goals)
        $forecastData = $forecastService->forecastUserSales($user->getVentes()->toArray());

        // Configure Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Arial');
        $pdfOptions->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($pdfOptions);

        $html = $this->renderView('front/ventes_depenses/report_pdf.html.twig', [
            'user' => $user,
            'ventes' => $user->getVentes(),
            'depenses' => $user->getDepenses(),
            'totalExpenses' => $totalExpenses,
            'totalSales' => $totalSales,
            'netProfit' => $netProfit,
            'forecastData' => $forecastData,
        ]);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = 'rapport_mensuel_' . $user->getNom() . '_' . date('m_Y') . '.pdf';

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"'
        ]);
    }

    // ← AJOUTE CE QUI SUIT
    #[Route('/profil', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('front/utilisateurs/profil.html.twig');
    }
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/services', name: 'app_services')]
public function services(): Response
{
    return $this->render('front/home/services.html.twig');
}
#[Route('/tech', name: 'app_tech_home')]
public function techHome(): Response
{
    return $this->render('front/home/tech_home.html.twig');
}

    #[Route('/utilisateurs/login', name: 'front_login')]
    public function login(): Response
    {
        return $this->render('front/utilisateurs/login.html.twig');
    }

    #[Route('/utilisateurs/register', name: 'front_register')]
    public function register(): Response
    {
        return $this->render('front/utilisateurs/register.html.twig');
    }
}