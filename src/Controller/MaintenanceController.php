<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Entity\Tache;
use App\Form\MaintenanceType;
use App\Entity\User;
use App\Repository\MaintenanceRepository;
use App\Repository\TacheRepository;
use App\Service\MaintenanceRecommendationService;
use App\Service\MaintenanceProximityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class MaintenanceController extends AbstractController
{
   
#[Route('/maintenance', name: 'app_maintenance')]
public function index(Request $request, MaintenanceRepository $repo): Response
{
    $search = $request->query->get('q');
    $status = $request->query->get('s');

    $sessionUser = $request->getSession()->get('user');

    if (!$sessionUser) {
        return $this->redirectToRoute('front_login');
    }

    
    $userId = is_object($sessionUser) ? $sessionUser->getId() : $sessionUser;

    
    $maintenances = $repo->findByFilters($search, $status, $userId);

    return $this->render('front/maintenance/maintenance.html.twig', [
        'listeMaintenances' => $maintenances,
        'currentSearch' => $search,
        'currentStatus' => $status,
    ]);
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

            if ($etat !== 1 && $isPast && $etat !== -1) {
                $tache->setEtat(-1);
                $hasEtatChanges = true;
            }

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

    #[Route('/maintenance/recommendations', name: 'app_maintenance_recommendations', methods: ['POST'])]
    public function maintenanceRecommendations(
        Request $request,
        MaintenanceRepository $maintenanceRepository,
        MaintenanceRecommendationService $recommendationService,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $sessionUser = $request->getSession()->get('user');
        $technician = null;

        if (is_object($sessionUser) && method_exists($sessionUser, 'getId') && method_exists($sessionUser, 'getRole') && (int) $sessionUser->getRole() === 2) {
            $technician = $entityManager->getRepository(User::class)->find($sessionUser->getId());
        } elseif (is_array($sessionUser) && isset($sessionUser['id'], $sessionUser['role']) && (int) $sessionUser['role'] === 2) {
            $technician = $entityManager->getRepository(User::class)->find((int) $sessionUser['id']);
        }

        if (!$technician instanceof User) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Session technicien invalide.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $maintenances = $maintenanceRepository->findBy(['statut' => 'En attente']);
        $technicianAddress = $technician->getAdresse();
        $limit = max(1, min(5, (int) $request->query->get('limit', 3)));

        $payload = $recommendationService->recommendForTechnician($technician, $maintenances, $technicianAddress, $limit);

        return new JsonResponse([
            'success' => true,
            'message' => $payload['message'],
            'data' => $payload,
        ]);
    }

    #[Route('/maintenance/planifiee', name: 'app_maintenance_planifiee')]
    public function maintenancePlanifiee(
        MaintenanceRepository $maintenanceRepository,
        MaintenanceProximityService $proximityService,
        Request $request
    ): Response {
        $maintenances = $maintenanceRepository->findBy(['statut' => 'Planifiée']);

        $sessionUser = $request->getSession()->get('user');
        $technicianAddress = (is_object($sessionUser) && method_exists($sessionUser, 'getAdresse'))
            ? (string) $sessionUser->getAdresse()
            : null;

        $proximityResult = $proximityService->sortByRoadDistance($maintenances, $technicianAddress);

        return $this->render('front/maintenance/interventions_planifiees.html.twig', [
            'listeMaintenances' => $proximityResult['maintenances'],
            'maintenanceDistances' => $proximityResult['distances'],
            'proximityEnabled' => $proximityResult['enabled'],
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
            $isOverloaded = $isCurrentMonth && $dayLoad > $threshold;

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

    #[Route('/maintenance/detail/{id_maintenance}', name: 'app_maintenance_detail')]
    public function detail(Maintenance $maintenance): Response
    {
        return $this->render('front/maintenance/maintenance_tache_detail.html.twig', [
            'maintenance' => $maintenance,
        ]);
    }

 #[Route('/maintenance/ajouter', name: 'app_maintenance_add')]
public function add(Request $request, EntityManagerInterface $em): Response
{
    // 1. On récupère ce qu'il y a en session
    $sessionUser = $request->getSession()->get('user');

    // 2. On vérifie si on a quelque chose (soit l'objet, soit l'ID)
    // On ne fait plus de "instanceof User" ici car c'est cela qui cause la redirection
    if (!$sessionUser) {
        return $this->redirectToRoute('front_login');
    }

    // 3. On récupère l'ID (que ce soit un objet ou un entier)
    $userId = (is_object($sessionUser)) ? $sessionUser->getId() : $sessionUser;

    // 4. On recharge l'utilisateur "frais" depuis la base de données
    $agriculteur = $em->getRepository(User::class)->find($userId);
    
    if (!$agriculteur) {
        return $this->redirectToRoute('front_login');
    }

    // 5. Initialisation de la maintenance
    $maintenance = new Maintenance();
    $maintenance->setDateDeclaration(new \DateTime());
    $maintenance->setStatut('En attente');
    
    // Affectation de l'agriculteur connecté
    $maintenance->setId_agriculteur($agriculteur);

    $form = $this->createForm(MaintenanceType::class, $maintenance);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->persist($maintenance);
        $em->flush();
        
        return $this->redirectToRoute('app_maintenance');
    }

    return $this->render('front/maintenance/new.html.twig', [
        'form' => $form->createView(),
    ]);
}
    #[Route('/maintenance/modifier/{id_maintenance}', name: 'app_maintenance_edit')]
    public function edit(Maintenance $maintenance, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(MaintenanceType::class, $maintenance);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_maintenance');
        }

        return $this->render('front/maintenance/edit.html.twig', [
            'form' => $form->createView(),
            'maintenance' => $maintenance
        ]);
    }

    #[Route('/maintenance/supprimer/{id_maintenance}', name: 'app_maintenance_delete', methods: ['POST'])]
    public function delete(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$maintenance->getId_maintenance(), $request->request->get('_token'))) {
            $em->remove($maintenance);
            $em->flush();
          
        }

        return $this->redirectToRoute('app_maintenance');
    }

#[Route('/back/maintenance/supprimer/{id_maintenance}', name: 'app_maintenance_delete_back', methods: ['POST'])]
public function deleteBack(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
{
    if ($this->isCsrfTokenValid('delete'.$maintenance->getId_maintenance(), $request->request->get('_token'))) {
        $em->remove($maintenance);
        $em->flush();

        
    }

    return $this->redirectToRoute('app_maintenance_back_list');
}

#[Route('/back/maintenance', name: 'app_maintenance_back_list', priority: 2)]
public function backList(
    Request $request,
    MaintenanceRepository $repo
): Response
{
    $search = $request->query->get('q');
    $status = $request->query->get('s');
    $priority = $request->query->get('p'); 

   
    $maintenances = $repo->findByFilters($search, $status, $priority);
    $notificationContext = $this->buildMaintenanceNotificationContext($repo);

    return $this->render('back/maintenance/maintenance.html.twig', [
        'maintenances' => $maintenances,
        'pendingNotifications' => $notificationContext['pendingNotifications'],
        'pendingCount' => $notificationContext['pendingCount'],
        'unreadCount' => $notificationContext['unreadCount'],
    ]);
}

#[Route('/back/maintenance/detail/{id_maintenance}', name: 'app_maintenance_detail_back')]
public function showBack(Maintenance $maintenance, EntityManagerInterface $entityManager, MaintenanceRepository $repo): Response 
{
    if (!$maintenance->isRead()) {
        $maintenance->setIsRead(true);
        $entityManager->flush();
    }

    $notificationContext = $this->buildMaintenanceNotificationContext($repo);

   
    return $this->render('back/maintenance/show.html.twig', [
        'maintenance' => $maintenance,
        'pendingNotifications' => $notificationContext['pendingNotifications'],
        'pendingCount' => $notificationContext['pendingCount'],
        'unreadCount' => $notificationContext['unreadCount'],
    ]);
}
#[Route('/back/maintenance/statistiques', name: 'app_maintenance_stats_back')]
public function statsBack(MaintenanceRepository $repo, TacheRepository $tacheRepo): Response
{
    $maintenances = $repo->findAll();
    
    $statsStatut = ['Resolu' => 0, 'Attente' => 0, 'Planifie' => 0, 'Refuse' => 0];
    $statsPriorite = ['Urgente' => 0, 'Normale' => 0, 'Faible' => 0];
    $statsParJour = [];

    foreach ($maintenances as $m) {
        // --- Stats Statut ---
        $s = $m->getStatut();
        if (in_array($s, ['Résolu', 'Résolue'])) $statsStatut['Resolu']++;
        elseif (in_array($s, ['En attente', 'Attente', 'En Attente'])) $statsStatut['Attente']++;
        elseif (in_array($s, ['Planifié', 'Planifiée'])) $statsStatut['Planifie']++;
        elseif (in_array($s, ['Refusé', 'Refusée'])) $statsStatut['Refuse']++;

       
        $p = trim($m->getPriorite()); 
        
        if (in_array($p, ['Urgente', 'Urgent', 'urgente'])) {
            $statsPriorite['Urgente']++;
        } elseif (in_array($p, ['Normale', 'Normal', 'normale', 'moyenne'])) {
            $statsPriorite['Normale']++;
        } elseif (in_array($p, ['Faible', 'Low', 'faible'])) {
            $statsPriorite['Faible']++;
        }

        // --- Stats par Jour ---
        if ($m->getDateDeclaration()) {
            $dateStr = $m->getDateDeclaration()->format('d/m/Y');
            $statsParJour[$dateStr] = ($statsParJour[$dateStr] ?? 0) + 1;
        }
    }

    // --- Stats Évaluations Négatives par Technicien ---
    $techniciansNegativeEval = $tacheRepo->getTechniciansWithNegativeEvaluations();
$techniciansPositiveEval = $tacheRepo->getTechniciansWithPositiveEvaluations();
    $notificationContext = $this->buildMaintenanceNotificationContext($repo);

    return $this->render('back/maintenance/stats.html.twig', [
        'total' => count($maintenances),
        'statsStatut' => $statsStatut,
        'statsPriorite' => $statsPriorite,
        'statsParJour' => $statsParJour,
        'techniciansNegativeEval' => $techniciansNegativeEval,
        'techniciansNegativeEval' => $techniciansNegativeEval,
    'techniciansPositiveEval' => $techniciansPositiveEval,
    'pendingNotifications' => $notificationContext['pendingNotifications'],
    'pendingCount' => $notificationContext['pendingCount'],
    'unreadCount' => $notificationContext['unreadCount'],
    ]);
}
#[Route('/back/maintenance/notifications', name: 'app_maintenance_notifications_back')]
public function notifications(MaintenanceRepository $repo): Response
{
    $notificationContext = $this->buildMaintenanceNotificationContext($repo);

    return $this->render('back/maintenance/notifications.html.twig', [
        'notifications' => $notificationContext['pendingNotifications'],
        'pendingNotifications' => $notificationContext['pendingNotifications'],
        'pendingCount' => $notificationContext['pendingCount'],
        'unreadCount' => $notificationContext['unreadCount'],
    ]);
}

#[Route('/back/maintenance/notifications/state', name: 'app_maintenance_notifications_state_back', methods: ['GET'])]
public function notificationsState(MaintenanceRepository $repo): JsonResponse
{
    $notificationContext = $this->buildMaintenanceNotificationContext($repo);
    $readMap = [];

    foreach ($notificationContext['pendingNotifications'] as $maintenance) {
        $readMap[(int) $maintenance->getId_maintenance()] = $maintenance->isRead();
    }

    return new JsonResponse([
        'pendingCount' => $notificationContext['pendingCount'],
        'unreadCount' => $notificationContext['unreadCount'],
        'readMap' => $readMap,
    ]);
}

#[Route('/back/maintenance/notifications/mark-read/{id_maintenance}', name: 'app_maintenance_notification_mark_read_back', methods: ['POST'])]
public function markNotificationReadBack(int $id_maintenance, MaintenanceRepository $repo, EntityManagerInterface $entityManager): Response
{
    $maintenance = $repo->find($id_maintenance);
    if ($maintenance instanceof Maintenance && !$maintenance->isRead()) {
        $maintenance->setIsRead(true);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_maintenance_notifications_back');
}

#[Route('/back/maintenance/notifications/mark-all-read', name: 'app_maintenance_notification_mark_all_read_back', methods: ['POST'])]
public function markAllNotificationsReadBack(MaintenanceRepository $repo, EntityManagerInterface $entityManager): Response
{
    $pendingNotifications = $repo->findBy(['statut' => 'En attente'], ['date_declaration' => 'DESC']);
    foreach ($pendingNotifications as $maintenance) {
        if (!$maintenance->isRead()) {
            $maintenance->setIsRead(true);
        }
    }
    $entityManager->flush();

    return $this->redirectToRoute('app_maintenance_notifications_back');
}
#[Route('/back/maintenance/refuser/{id_maintenance}', name: 'app_maintenance_refuse_back', methods: ['POST'])]
public function refuseBack(Maintenance $maintenance, EntityManagerInterface $em): Response
{
    $maintenance->setStatut('Refusée'); 
    
    $em->flush();
   

    return $this->redirectToRoute('app_maintenance_back_list');
}



public function countPendingNotifications(MaintenanceRepository $repo): Response
{
    $notificationContext = $this->buildMaintenanceNotificationContext($repo);
    $count = $notificationContext['unreadCount'];

    return new Response($count);
}

/**
 * @return array{pendingNotifications: Maintenance[], pendingCount: int, unreadCount: int}
 */
private function buildMaintenanceNotificationContext(MaintenanceRepository $repo): array
{
    $pendingNotifications = $repo->findBy(
        ['statut' => 'En attente'],
        ['date_declaration' => 'DESC', 'id_maintenance' => 'DESC']
    );

    $unreadCount = count(array_filter(
        $pendingNotifications,
        static fn (Maintenance $maintenance): bool => !$maintenance->isRead()
    ));

    return [
        'pendingNotifications' => $pendingNotifications,
        'pendingCount' => count($pendingNotifications),
        'unreadCount' => $unreadCount,
    ];
}

#[Route('/tache/evaluer/{id}/{note}', name: 'app_tache_evaluer')]
public function evaluer(Tache $tache, int $note, EntityManagerInterface $em) {
    $tache->setEvaluation($note);
    $em->flush();
    return $this->redirectToRoute('app_maintenance_show', ['id' => $tache->getMaintenance()->getId()]);
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
        'maintenanceLieu' => $maintenance?->getLieu() ?? 'Lieu inconnu',
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
