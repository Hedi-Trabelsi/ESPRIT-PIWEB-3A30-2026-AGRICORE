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
    // ON GARDE SEULEMENT CETTE VERSION DE INDEX (Recherche + Filtre)
    #[Route('/maintenance', name: 'app_maintenance')]
    public function index(Request $request, MaintenanceRepository $repo): Response
    {
        // On récupère les valeurs envoyées par le formulaire (URL)
        $search = $request->query->get('q');
        $status = $request->query->get('s', 'Planifiée');

        // On utilise ta nouvelle méthode DQL du Repository
        $maintenances = $repo->findBySearchAndStatus($search, $status);

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
public function backList(MaintenanceRepository $repo): Response
{
    
    $maintenances = $repo->findAll();

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
}