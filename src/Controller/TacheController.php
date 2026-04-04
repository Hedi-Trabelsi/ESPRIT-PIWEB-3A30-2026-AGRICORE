<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Entity\Tache;
use App\Entity\User; 
use App\Form\TacheType; 
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TacheController extends AbstractController
{
    #[Route('/tache/nouvelle/{id_maintenance}', name: 'app_tache_new', defaults: ['id_maintenance' => null])]
    public function new(Request $request, EntityManagerInterface $em, MaintenanceRepository $maintenanceRepository, ?int $id_maintenance = null): Response
    {
        $tache = new Tache();

        // 1. Récupérer le technicien ID 4
        $technicien = $em->getRepository(User::class)->find(4);
        if ($technicien) {
            $tache->setIdTechnicien($technicien);
        }

        // 2. Pré-remplir la maintenance
        if ($id_maintenance) {
            $maintenance = $maintenanceRepository->find($id_maintenance);
            if ($maintenance) {
                $tache->setIdMaintenance($maintenance);
            }
        }

        // --- CORRECTION DE L'ERREUR DE DATE ICI ---
        // On donne la date du jour par défaut pour éviter le "null"
        $tache->setDatePrevue(new \DateTime()); 
        
        $tache->setEvaluation(0);

        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($tache);
            $em->flush();

            return $this->redirectToRoute('app_maintenance');
        }

        return $this->render('front/maintenance/new_tache.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tache/modifier/{id_tache}', name: 'app_tache_edit')]
    public function edit(Tache $tache, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('app_maintenance_taches', [
                'id_maintenance' => $tache->getIdMaintenance()->getId_maintenance(),
            ]);
        }

        return $this->render('front/maintenance/edit_tache.html.twig', [
            'form' => $form->createView(),
            'tache' => $tache,
        ]);
    }

    #[Route('/tache/supprimer/{id_tache}', name: 'app_tache_delete', methods: ['POST'])]
    public function delete(Request $request, Tache $tache, EntityManagerInterface $em): Response
    {
        $maintenanceId = $tache->getIdMaintenance()->getId_maintenance();

        if ($this->isCsrfTokenValid('delete'.$tache->getId_tache(), $request->request->get('_token'))) {
            $em->remove($tache);
            $em->flush();

            $this->addFlash('success', 'Tâche supprimée avec succès.');
        }

        return $this->redirectToRoute('app_maintenance_taches', [
            'id_maintenance' => $maintenanceId,
        ]);
    }
}