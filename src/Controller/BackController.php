<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\EvennementagricoleRepository;
use App\Repository\MaintenanceRepository;
use App\Repository\VenteRepository;
use App\Repository\DepenseRepository;
use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BackController extends AbstractController
{
    #[Route('/dash', name: 'back_dashboard')]
    public function dashboard(
        UserRepository $userRepo,
        EvennementagricoleRepository $evenementRepo,
        MaintenanceRepository $maintenanceRepo,
        VenteRepository $venteRepo,
        DepenseRepository $depenseRepo,
        AnimalRepository $animalRepo,
        SuiviAnimalRepository $suiviRepo
    ): Response {
        // --- Counts ---
        $totalUsers = $userRepo->count([]);
        $totalEvenements = $evenementRepo->count([]);
        $totalMaintenances = $maintenanceRepo->count([]);
        $totalAnimaux = $animalRepo->count([]);

        // --- User Role Distribution ---
        $users = $userRepo->findAll();
        $roleCounts = ['Admin' => 0, 'Agriculteur' => 0, 'Technicien' => 0];
        foreach ($users as $u) {
            $role = $u->getRole();
            if ($role === 0) $roleCounts['Admin']++;
            elseif ($role === 1) $roleCounts['Agriculteur']++;
            elseif ($role === 2) $roleCounts['Technicien']++;
        }

        // --- Maintenance Status Breakdown ---
        $maintenances = $maintenanceRepo->findAll();
        $statusCounts = ['Resolu' => 0, 'Attente' => 0, 'Planifie' => 0, 'Refuse' => 0];
        foreach ($maintenances as $m) {
            $status = $m->getStatut();
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }

        // --- Animal Health States ---
        $suivis = $suiviRepo->findAll();
        $healthCounts = ['Bon' => 0, 'Moyen' => 0, 'Mauvais' => 0];
        foreach ($suivis as $s) {
            $health = $s->getEtatSante();
            if (isset($healthCounts[$health])) {
                $healthCounts[$health]++;
            }
        }

        // --- Financial Stats ---
        $ventes = $venteRepo->findAll();
        $totalVentes = 0;
        foreach ($ventes as $v) {
            $totalVentes += $v->getChiffreAffaires();
        }

        $depenses = $depenseRepo->findAll();
        $totalDepenses = 0;
        foreach ($depenses as $d) {
            $totalDepenses += $d->getMontant();
        }

        $profit = $totalVentes - $totalDepenses;

        // --- Monthly Finance Data ---
        $currentYear = (int)date('Y');
        $ventesData = array_fill(1, 12, 0);
        $ventesThisMonth = 0;
        $currentMonth = (int)date('m');
        
        foreach ($ventes as $v) {
            $vYear = (int)$v->getDate()->format('Y');
            $vMonth = (int)$v->getDate()->format('m');
            if ($vYear === $currentYear) {
                $ventesData[$vMonth] += $v->getChiffreAffaires();
                if ($vMonth === $currentMonth) {
                    $ventesThisMonth += $v->getChiffreAffaires();
                }
            }
        }

        $depensesData = array_fill(1, 12, 0);
        foreach ($depenses as $d) {
            $dYear = (int)$d->getDate()->format('Y');
            $dMonth = (int)$d->getDate()->format('m');
            if ($dYear === $currentYear) {
                $depensesData[$dMonth] += $d->getMontant();
            }
        }

        // --- Performance Percentage (Mock logic for progress bars) ---
        $userGrowth = $totalUsers > 0 ? min(100, ($totalUsers / 50) * 100) : 0; // Target 50 users
        $eventSaturation = $totalEvenements > 0 ? min(100, ($totalEvenements / 20) * 100) : 0; // Target 20 events
        $maintenanceEfficiency = $totalMaintenances > 0 ? min(100, ($statusCounts['Resolu'] / $totalMaintenances) * 100) : 0;
        $animalHealthIndex = $totalAnimaux > 0 ? min(100, ($healthCounts['Bon'] / max(1, array_sum($healthCounts))) * 100) : 0;

        // --- Animal Species distribution ---
        $animaux = $animalRepo->findAll();
        $speciesData = [];
        foreach ($animaux as $a) {
            $sp = $a->getEspece();
            $speciesData[$sp] = ($speciesData[$sp] ?? 0) + 1;
        }

        // --- Recent Activities ---
        $recentMaintenances = $maintenanceRepo->findBy([], ['date_declaration' => 'DESC'], 5);
        $upcomingEvents = $evenementRepo->createQueryBuilder('e')
            ->where('e.date_debut >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date_debut', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('back/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalEvenements' => $totalEvenements,
            'totalMaintenances' => $totalMaintenances,
            'totalAnimaux' => $totalAnimaux,
            'totalVentes' => $totalVentes,
            'totalDepenses' => $totalDepenses,
            'profit' => $profit,
            'ventesThisMonth' => $ventesThisMonth,
            'ventesChartData' => array_values($ventesData),
            'depensesChartData' => array_values($depensesData),
            'speciesLabels' => array_keys($speciesData),
            'speciesCounts' => array_values($speciesData),
            'roleLabels' => array_keys($roleCounts),
            'roleCounts' => array_values($roleCounts),
            'statusLabels' => array_keys($statusCounts),
            'statusCounts' => array_values($statusCounts),
            'healthLabels' => array_keys($healthCounts),
            'healthCounts' => array_values($healthCounts),
            'recentMaintenances' => $recentMaintenances,
            'upcomingEvents' => $upcomingEvents,
            'metrics' => [
                'userGrowth' => $userGrowth,
                'eventSaturation' => $eventSaturation,
                'maintenanceEfficiency' => $maintenanceEfficiency,
                'animalHealthIndex' => $animalHealthIndex
            ]
        ]);
    }

    #[Route('/back/maintenance', name: 'back_maintenance')]
    public function maintenance(): Response
    {
        return $this->render('back/maintenance/maintenance.html.twig');
    }

    #[Route('/back/equipements', name: 'back_equipements')]
    public function equipements(Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        return $this->redirectToRoute('back_equipement_index');
    }

}
