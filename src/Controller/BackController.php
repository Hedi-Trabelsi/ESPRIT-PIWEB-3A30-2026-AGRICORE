<?php

namespace App\Controller;

use App\Entity\User;
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
        $statusCounts = ['Resolu' => 0, 'Attente' => 0, 'Planifie' => 0, 'Refuse' => 0, 'Accepte' => 0];
        $pendingNotifications = [];

        foreach ($maintenances as $m) {
            $status = mb_strtolower(trim((string) $m->getStatut()));

            if (in_array($status, ['resolu', 'résolu', 'résolue'], true)) {
                $statusCounts['Resolu']++;
            } elseif (in_array($status, ['en attente', 'attente'], true)) {
                $statusCounts['Attente']++;
                $pendingNotifications[] = $m;
            } elseif (in_array($status, ['accepter', 'accepté', 'acceptée', 'accepte', 'acceptee'], true)) {
                $statusCounts['Accepte']++;
            } elseif (in_array($status, ['planifier', 'planifié', 'planifiée'], true)) {
                $statusCounts['Planifie']++;
            } elseif (in_array($status, ['refuse', 'refusé', 'refusée', 'refusee'], true)) {
                $statusCounts['Refuse']++;
            }
        }

        usort($pendingNotifications, static function ($left, $right): int {
            $leftDate = $left->getDateDeclaration();
            $rightDate = $right->getDateDeclaration();

            if ($leftDate && $rightDate) {
                $compare = $rightDate <=> $leftDate;
                if ($compare !== 0) {
                    return $compare;
                }
            } elseif ($leftDate) {
                return -1;
            } elseif ($rightDate) {
                return 1;
            }

            return $right->getId_maintenance() <=> $left->getId_maintenance();
        });

        $unreadCount = count(array_filter(
            $pendingNotifications,
            static fn ($maintenance): bool => !$maintenance->isRead()
        ));

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
            $vDate = $v->getDate();
            if ($vDate === null) continue;
            $vYear = (int)$vDate->format('Y');
            $vMonth = (int)$vDate->format('m');
            if ($vYear === $currentYear) {
                $ventesData[$vMonth] += $v->getChiffreAffaires();
                if ($vMonth === $currentMonth) {
                    $ventesThisMonth += $v->getChiffreAffaires();
                }
            }
        }

        $depensesData = array_fill(1, 12, 0);
        foreach ($depenses as $d) {
            $dDate = $d->getDate();
            if ($dDate === null) continue;
            $dYear = (int)$dDate->format('Y');
            $dMonth = (int)$dDate->format('m');
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
            'pendingNotifications' => $pendingNotifications,
            'pendingCount' => count($pendingNotifications),
            'unreadCount' => $unreadCount,
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
        if (!$sessionUser instanceof User || $sessionUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        return $this->redirectToRoute('back_equipement_index');
    }

}
