<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Form\MaintenanceType;
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
   
    $maintenances = $repo->findByFilters($search, $status, null);

    return $this->render('front/maintenance/maintenance.html.twig', [
        'listeMaintenances' => $maintenances,
        'currentSearch' => $search,
        'currentStatus' => $status, // Sera null au premier chargement
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
        $maintenance = new Maintenance();
        $maintenance->setDateDeclaration(new \DateTime());
        $maintenance->setStatut('En attente'); 

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
public function backList(Request $request, MaintenanceRepository $repo): Response
{
    $search = $request->query->get('q');
    $status = $request->query->get('s');
    $priority = $request->query->get('p'); // On récupère la priorité

    // Appelle une méthode personnalisée dans ton Repository
    $maintenances = $repo->findByFilters($search, $status, $priority);

    return $this->render('back/maintenance/maintenance.html.twig', [
        'maintenances' => $maintenances,
    ]);
}

#[Route('/back/maintenance/detail/{id_maintenance}', name: 'app_maintenance_detail_back')]
public function showBack(Maintenance $maintenance): Response 
{
   
    return $this->render('back/maintenance/show.html.twig', [
        'maintenance' => $maintenance,
    ]);
}
#[Route('/back/maintenance/statistiques', name: 'app_maintenance_stats_back')]
public function statsBack(MaintenanceRepository $repo): Response
{
    $maintenances = $repo->findAll();
    
    // INITIALISATION CRUCIALE : On s'assure que les clés existent dès le début
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

        // --- Stats Priorité CORRIGÉES ---
        // On utilise trim() pour enlever les espaces invisibles
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
public function notifications(MaintenanceRepository $repo): Response
{
   
    $enAttente = $repo->findBy(
        ['statut' => 'En attente'], 
        ['date_declaration' => 'DESC'] 
    );

    return $this->render('back/maintenance/notifications.html.twig', [
        'notifications' => $enAttente,
    ]);
}
#[Route('/back/maintenance/refuser/{id_maintenance}', name: 'app_maintenance_refuse_back', methods: ['POST'])]
public function refuseBack(Maintenance $maintenance, EntityManagerInterface $em): Response
{
    $maintenance->setStatut('Refusée'); 
    
    $em->flush();
   

    return $this->redirectToRoute('app_maintenance_back_list');
}

// src/Controller/MaintenanceController.php

public function countPendingNotifications(MaintenanceRepository $repo): Response
{
    // On compte uniquement les maintenances "En attente"
    $count = $repo->count(['statut' => 'En attente']);

    return new Response($count);
}

#[Route('/tache/evaluer/{id}/{note}', name: 'app_tache_evaluer')]
public function evaluer(Tache $tache, int $note, EntityManagerInterface $em) {
    $tache->setEvaluation($note);
    $em->flush();
    return $this->redirectToRoute('app_maintenance_show', ['id' => $tache->getMaintenance()->getId()]);
}


}