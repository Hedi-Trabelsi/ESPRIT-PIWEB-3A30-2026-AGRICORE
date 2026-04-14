<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Entity\Tache;
use App\Form\MaintenanceType;
use App\Entity\User;
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

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
    $this->cleanReadNotificationIds($request, $repo);
    $search = $request->query->get('q');
    $status = $request->query->get('s');
    $priority = $request->query->get('p'); 

   
    $maintenances = $repo->findByFilters($search, $status, $priority);
    $pendingNotifications = $repo->findBy(['statut' => 'En attente'], ['date_declaration' => 'DESC']);
    $readNotificationIds = $this->getReadNotificationIds($request);
    $unreadNotifications = array_values(array_filter(
        $pendingNotifications,
        static fn (Maintenance $maintenance): bool => !in_array((int) $maintenance->getId_maintenance(), $readNotificationIds, true)
    ));

    $pendingCount = count($pendingNotifications);
    $unreadCount = count($unreadNotifications);

    return $this->render('back/maintenance/maintenance.html.twig', [
        'maintenances' => $maintenances,
        'pendingNotifications' => $pendingNotifications,
        'readNotificationIds' => $readNotificationIds,
        'pendingCount' => $pendingCount,
        'unreadCount' => $unreadCount,
    ]);
}

#[Route('/back/maintenance/detail/{id_maintenance}', name: 'app_maintenance_detail_back')]
public function showBack(Maintenance $maintenance, Request $request): Response 
{
    $readIds = $this->getReadNotificationIds($request);
    $maintenanceId = (int) $maintenance->getId_maintenance();
    if (!in_array($maintenanceId, $readIds, true)) {
        $readIds[] = $maintenanceId;
        $this->saveReadNotificationIds($request, $readIds);
    }

   
    return $this->render('back/maintenance/show.html.twig', [
        'maintenance' => $maintenance,
    ]);
}
#[Route('/back/maintenance/statistiques', name: 'app_maintenance_stats_back')]
public function statsBack(MaintenanceRepository $repo): Response
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

    return $this->render('back/maintenance/stats.html.twig', [
        'total' => count($maintenances),
        'statsStatut' => $statsStatut,
        'statsPriorite' => $statsPriorite,
        'statsParJour' => $statsParJour,
    ]);
}
#[Route('/back/maintenance/notifications', name: 'app_maintenance_notifications_back')]
public function notifications(
    Request $request,
    MaintenanceRepository $repo
): Response
{
   
$pendingNotifications = $repo->findBy(
    ['statut' => 'En attente'], 
    ['date_declaration' => 'DESC', 'id_maintenance' => 'DESC']
);
    $readNotificationIds = $this->getReadNotificationIds($request);
    $unreadCount = count(array_filter(
        $pendingNotifications,
        static fn (Maintenance $maintenance): bool => !in_array((int) $maintenance->getId_maintenance(), $readNotificationIds, true)
    ));

    return $this->render('back/maintenance/notifications.html.twig', [
        'notifications' => $pendingNotifications,
        'readNotificationIds' => $readNotificationIds,
        'pendingCount' => count($pendingNotifications),
        'unreadCount' => $unreadCount,
    ]);
}

#[Route('/back/maintenance/notifications/mark-read/{id_maintenance}', name: 'app_maintenance_notification_mark_read_back', methods: ['POST'])]
public function markNotificationReadBack(int $id_maintenance, Request $request): Response
{
    $readIds = $this->getReadNotificationIds($request);
    $readIds[] = (int) $id_maintenance;
    $this->saveReadNotificationIds($request, $readIds);

    return $this->redirectToRoute('app_maintenance_notifications_back');
}

#[Route('/back/maintenance/notifications/mark-all-read', name: 'app_maintenance_notification_mark_all_read_back', methods: ['POST'])]
public function markAllNotificationsReadBack(Request $request, MaintenanceRepository $repo): Response
{
    $pendingNotifications = $repo->findBy(['statut' => 'En attente'], ['date_declaration' => 'DESC']);
    $ids = array_map(static fn (Maintenance $maintenance): int => (int) $maintenance->getId_maintenance(), $pendingNotifications);
    $this->saveReadNotificationIds($request, $ids);

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
    
    $count = $repo->count(['statut' => 'En attente']);

    return new Response($count);
}

#[Route('/tache/evaluer/{id}/{note}', name: 'app_tache_evaluer')]
public function evaluer(Tache $tache, int $note, EntityManagerInterface $em) {
    $tache->setEvaluation($note);
    $em->flush();
    return $this->redirectToRoute('app_maintenance_show', ['id' => $tache->getMaintenance()->getId()]);
}

/**
 * @return int[]
 */
private function getReadNotificationIds(Request $request): array
{
    $session = $request->getSession();
    $userSuffix = $this->resolveSessionUserKey($request);
    $key = 'maintenance_notifications_read_' . $userSuffix;
    $ids = $session->get($key, []);

    if (!is_array($ids)) {
        return [];
    }

    return array_values(array_unique(array_map('intval', $ids)));
}

/**
 * @param int[] $ids
 */
private function saveReadNotificationIds(Request $request, array $ids): void
{
    $session = $request->getSession();
    $userSuffix = $this->resolveSessionUserKey($request);
    $key = 'maintenance_notifications_read_' . $userSuffix;
    $session->set($key, array_values(array_unique(array_map('intval', $ids))));
}

private function resolveSessionUserKey(Request $request): string
{
    $sessionUser = $request->getSession()->get('user');

    if (is_object($sessionUser) && method_exists($sessionUser, 'getId')) {
        return (string) $sessionUser->getId();
    }

    if (is_scalar($sessionUser)) {
        return (string) $sessionUser;
    }

    return 'anonymous';
}
 private function cleanReadNotificationIds(Request $request, MaintenanceRepository $repo): void
{
    $allPending = $repo->findBy(['statut' => 'En attente']);
    $currentPendingIds = array_map(fn($m) => (int)$m->getId_maintenance(), $allPending);
    
    $readIds = $this->getReadNotificationIds($request);
    // On ne garde que les IDs qui sont toujours en attente
    $validReadIds = array_values(array_intersect($readIds, $currentPendingIds));
    
    $this->saveReadNotificationIds($request, $validReadIds);
}

}
