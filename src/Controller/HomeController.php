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
    public function userDetails(int $id, UserRepository $userRepository, AnomalyService $anomalyService): Response
    {
        $user = $userRepository->find($id);
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
            'anomalyRate' => $anomalyRate
        ]);
    }

    // ← AJOUTE CE QUI SUIT
    #[Route('/profil', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('front/utilisateurs/profil.html.twig');
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