<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Service\MaintenanceProximityService;
use App\Repository\MaintenanceRepository;
use App\Repository\TacheRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\AnimalRepository;
use Knp\Component\Pager\PaginatorInterface;

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

    #[Route('/maintenance/{id_maintenance}/taches', name: 'app_maintenance_taches')]
    public function maintenanceTaches(
        MaintenanceRepository $maintenanceRepository,
        EntityManagerInterface $entityManager,
        int $id_maintenance
    ): Response
    {
        $maintenance = $maintenanceRepository->find($id_maintenance);
        
        if (!$maintenance) {
            throw $this->createNotFoundException('Maintenance non trouvée');
        }

        $todayKey = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $hasEtatChanges = false;
        $totalCost = 0;
        $taches = $maintenance->getTaches()->toArray();

        foreach ($taches as $tache) {
            $datePrevue = $tache->getDatePrevue();
            $isPast = $datePrevue instanceof \DateTimeInterface && $datePrevue->format('Y-m-d') < $todayKey;
            $etat = $tache->getEtat();

            // Règle automatique: si la date est passée et non terminée, la tâche devient en retard.
            if ($etat !== 1 && $isPast && $etat !== -1) {
                $tache->setEtat(-1);
                $hasEtatChanges = true;
            }

            // Si la date n'est plus passée, on retire l'état retard automatique.
            if (!$isPast && $etat === -1) {
                $tache->setEtat(0);
                $hasEtatChanges = true;
            }

            $cout = $tache->getCoutEstimee();
            $totalCost += is_numeric($cout) ? (float) $cout : (float) str_replace([',', ' '], ['.', ''], $cout);
        }

        if ($hasEtatChanges) {
            $entityManager->flush();
        }

        usort($taches, static function (Tache $left, Tache $right): int {
            return $left->getDatePrevue() <=> $right->getDatePrevue();
        });

        return $this->render('front/maintenance/maintenance_taches.html.twig', [
            'maintenance' => $maintenance,
            'taches' => $taches,
            'totalCost' => $totalCost,
        ]);
    }

    #[Route('/maintenance/traiter', name: 'app_maintenance_traiter')]
    public function maintenanceATraiter(
        Request $request,
        MaintenanceRepository $maintenanceRepository,
        MaintenanceProximityService $proximityService,
        PaginatorInterface $paginator
    ): Response
    {
        $maintenances = $maintenanceRepository->findBy(['statut' => 'En attente']);

        $sessionUser = $request->getSession()->get('user');
        $technicianAddress = null;
        if (is_object($sessionUser) && method_exists($sessionUser, 'getAdresse')) {
            $technicianAddress = (string) $sessionUser->getAdresse();
        }

        $proximityResult = $proximityService->sortByRoadDistance($maintenances, $technicianAddress);
        $pagination = $paginator->paginate(
            $proximityResult['maintenances'],
            $request->query->getInt('page', 1),
            6
        );
        
        return $this->render('front/maintenance/interventions_a_traiter.html.twig', [
            'listeMaintenances' => $pagination,
            'maintenanceDistances' => $proximityResult['distances'],
            'proximityEnabled' => $proximityResult['enabled'],
        ]);
    }

#[Route('/maintenance/planifiee', name: 'app_maintenance_planifiee')]
public function maintenancePlanifiee(
    MaintenanceRepository $maintenanceRepository,
    MaintenanceProximityService $proximityService, // Injectez le service
    Request $request // Injectez la requête pour avoir l'utilisateur
): Response
{
    $maintenances = $maintenanceRepository->findBy(['statut' => 'Planifiée']);
    
    // Récupérer l'adresse du technicien (pour calculer la distance)
    $sessionUser = $request->getSession()->get('user');
    $technicianAddress = (is_object($sessionUser) && method_exists($sessionUser, 'getAdresse')) 
        ? (string)$sessionUser->getAdresse() 
        : null;

    // Utiliser le service pour obtenir les distances
    $proximityResult = $proximityService->sortByRoadDistance($maintenances, $technicianAddress);

    return $this->render('front/maintenance/interventions_planifiees.html.twig', [
        'listeMaintenances'    => $proximityResult['maintenances'], // Utilisez les maintenances triées
        'maintenanceDistances' => $proximityResult['distances'],    // <--- VOICI CE QUI MANQUAIT
        'proximityEnabled'     => $proximityResult['enabled'],      // <--- Variable de contrôle
    ]);
}

#[Route('/maintenance/historique', name: 'app_maintenance_historique')]
    public function Hmaintenance(MaintenanceRepository $maintenanceRepository): Response
    {
       
        $maintenancesResolues = $maintenanceRepository->findBy(['statut' => 'Résolue']);

        return $this->render('front/maintenance/HistoriqueMaintenances.html.twig', [
            'listeMaintenances' => $maintenancesResolues,
        ]);
    }
    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response
    {
        return $this->render('front/evenements/evenements.html.twig');
    }

  

     #[Route('/suivi-animal', name: 'app_suivi_animal')]
    public function suiviAnimal(Request $request, AnimalRepository $animalRepository): Response
    {
        $q = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'codeAnimal');
        $order = $request->query->get('order', 'ASC');

        $animals = $animalRepository->findAll(); // simple pour maintenant

        return $this->render('front/suivi_animal/animal/index.html.twig', [
            'animals' => $animals,
            'q' => $q,
            'sortBy' => $sortBy,
            'order' => $order,
        ]);
    }

    #[Route('/achat-equipement', name: 'app_achat_equipement')]
    public function achatEquipement(Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        return $this->redirectToRoute('app_equipement_catalogue');
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

    #[Route('/tech/maintenance/calendrier', name: 'app_tech_maintenance_calendar')]
    public function technicianMaintenanceCalendar(
        Request $request,
        TacheRepository $tacheRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $sessionUser = $request->getSession()->get('user');

        if (!is_object($sessionUser) || !method_exists($sessionUser, 'getRole') || (int) $sessionUser->getRole() !== 2) {
            return $this->redirectToRoute('front_login');
        }

        $technician = $entityManager->getRepository(User::class)->find($sessionUser->getId());
        if (!$technician) {
            return $this->redirectToRoute('front_login');
        }

        $monthParam = (string) $request->query->get('month', '');
        try {
            $selectedMonth = $monthParam !== ''
                ? new \DateTimeImmutable($monthParam . '-01')
                : new \DateTimeImmutable('first day of this month');
        } catch (\Throwable) {
            $selectedMonth = new \DateTimeImmutable('first day of this month');
        }

        $monthStart = $selectedMonth->setTime(0, 0, 0);
        $monthEnd = $monthStart->modify('last day of this month')->setTime(23, 59, 59);
        $today = new \DateTimeImmutable('today');
        $todayKey = $today->format('Y-m-d');
        $calendarStart = $monthStart->modify('-' . ((int) $monthStart->format('N') - 1) . ' days');
        $calendarEnd = $monthEnd->modify('+' . (7 - (int) $monthEnd->format('N')) . ' days');
        $calendarPeriod = new \DatePeriod($calendarStart, new \DateInterval('P1D'), $calendarEnd->modify('+1 day'));
        $hasEtatChanges = false;

        $tasksByDay = [];
        foreach ($tacheRepository->findByTechnicianAndDateRange($technician->getId(), $monthStart, $monthEnd) as $task) {
            $datePrevue = $task->getDatePrevue();
            $isPast = $datePrevue instanceof \DateTimeInterface && $datePrevue->format('Y-m-d') < $todayKey;

            if ($task->getEtat() !== 1 && $isPast && $task->getEtat() !== -1) {
                $task->setEtat(-1);
                $hasEtatChanges = true;
            }

            if ($task->getEtat() === -1 && !$isPast) {
                $task->setEtat(0);
                $hasEtatChanges = true;
            }

            $taskData = $this->buildCalendarTaskData($task, $todayKey);
            $taskDayKey = $taskData['dateKey'];
            $tasksByDay[$taskDayKey][] = $taskData;
        }

        foreach ($tasksByDay as &$dayTasks) {
            usort($dayTasks, static function (array $left, array $right): int {
                $stateRank = static fn (int $etat): int => match ($etat) {
                    -1 => 0,
                    0 => 1,
                    1 => 2,
                    default => 1,
                };

                if ($stateRank($left['etat']) === $stateRank($right['etat'])) {
                    return strcmp($left['taskName'], $right['taskName']);
                }

                return $stateRank($left['etat']) <=> $stateRank($right['etat']);
            });
        }
        unset($dayTasks);

        $calendarWeeks = [];
        $currentWeek = [];
        $overloadedDays = [];
        $threshold = 4;

        foreach ($calendarPeriod as $day) {
            $dayKey = $day->format('Y-m-d');
            $dayTasks = $tasksByDay[$dayKey] ?? [];
            $dayLoad = count(array_filter($dayTasks, static fn (array $task): bool => ($task['etat'] ?? 0) !== 1));
            $isCurrentMonth = $day->format('Y-m') === $monthStart->format('Y-m');
            $isPast = $dayKey < $todayKey;
            $isOverloaded = $isCurrentMonth && $dayLoad >= $threshold;

            $dayPayload = [
                'date' => $day,
                'dateKey' => $dayKey,
                'label' => $day->format('j'),
                'weekday' => (int) $day->format('N'),
                'weekdayLabel' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'][(int) $day->format('N') - 1],
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $dayKey === $todayKey,
                'isPast' => $isPast,
                'load' => $dayLoad,
                'isOverloaded' => $isOverloaded,
                'tasks' => $dayTasks,
            ];

            $currentWeek[] = $dayPayload;

            if ($isOverloaded) {
                $overloadedDays[] = $dayPayload;
            }

            if (count($currentWeek) === 7) {
                $calendarWeeks[] = $currentWeek;
                $currentWeek = [];
            }
        }

        $overdueTasks = [];
        foreach ($tacheRepository->findOverdueTasksForTechnician($technician->getId(), $today) as $task) {
            $taskData = $this->buildCalendarTaskData($task, $todayKey);
            if ($taskData['isOverdue'] && $taskData['etat'] !== 1) {
                $overdueTasks[] = $taskData;
            }
        }

        if ($hasEtatChanges) {
            $entityManager->flush();
        }

        usort($overdueTasks, static function (array $left, array $right): int {
            return strcmp($left['dateKey'], $right['dateKey']);
        });

        $todayTasks = $tasksByDay[$todayKey] ?? [];

        return $this->render('front/maintenance/technician_calendar.html.twig', [
            'monthLabel' => $this->formatFrenchMonthLabel($monthStart),
            'monthParam' => $monthStart->format('Y-m'),
            'previousMonth' => $monthStart->modify('-1 month')->format('Y-m'),
            'nextMonth' => $monthStart->modify('+1 month')->format('Y-m'),
            'calendarWeeks' => $calendarWeeks,
            'overdueTasks' => $overdueTasks,
            'todayTasks' => $todayTasks,
            'overloadedDays' => $overloadedDays,
            'totalTasks' => array_reduce($tasksByDay, static fn (int $carry, array $dayTasks): int => $carry + count($dayTasks), 0),
            'overdueCount' => count($overdueTasks),
            'overloadedCount' => count($overloadedDays),
            'threshold' => $threshold,
        ]);
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

    private function buildCalendarTaskData(Tache $task, string $todayKey): array
    {
        $taskDate = $task->getDatePrevue();
        $maintenance = $task->getIdMaintenance();
        $maintenanceStatus = $maintenance?->getStatut() ?? 'Inconnue';
        $isResolved = in_array($maintenanceStatus, ['Résolu', 'Résolue'], true);
        $dateKey = $taskDate?->format('Y-m-d') ?? '';
        $etat = $task->getEtat() ?? 0;
        $isOverdue = !$isResolved && ($etat === -1 || ($dateKey !== '' && $dateKey < $todayKey && $etat !== 1));
        $isToday = $dateKey === $todayKey;
        $daysLate = $isOverdue && $taskDate instanceof \DateTimeInterface
            ? (int) $taskDate->diff(new \DateTimeImmutable('today'))->format('%a')
            : 0;

        return [
            'id' => $task->getId_tache(),
            'date' => $taskDate,
            'dateKey' => $dateKey,
            'maintenanceId' => $maintenance?->getId_maintenance(),
            'taskName' => $task->getNomTache(),
            'maintenanceName' => $maintenance?->getNomMaintenance() ?? 'Maintenance',
            'maintenanceStatus' => $maintenanceStatus,
            'description' => $task->getDescription(),
            'technicianName' => $task->getIdTechnicien()
                ? trim(($task->getIdTechnicien()->getPrenom() ?? '') . ' ' . ($task->getIdTechnicien()->getNom() ?? ''))
                : 'Non assigné',
            'etat' => $etat,
            'isResolved' => $isResolved,
            'isOverdue' => $isOverdue,
            'isToday' => $isToday,
            'daysLate' => $daysLate,
            'stateLabel' => match ($etat) {
                1 => 'Terminée',
                -1 => 'En retard',
                default => ($isOverdue ? 'En retard' : 'À faire'),
            },
            'stateClass' => match ($etat) {
                1 => 'success',
                -1 => 'secondary',
                default => ($isOverdue ? 'secondary' : 'warning text-dark'),
            },
        ];
    }

    private function formatFrenchMonthLabel(\DateTimeInterface $date): string
    {
        $months = [
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
        ];

        return ucfirst($months[(int) $date->format('n')]) . ' ' . $date->format('Y');
    }
  
    
}
    
