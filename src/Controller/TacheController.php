<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Entity\Tache;
use App\Entity\User; 
use App\Form\TacheType; 
use App\Repository\MaintenanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TacheRepository;
use App\Service\MaintenancePlanningMailer;
use App\Service\TaskDescriptionAiService;
class TacheController extends AbstractController
{
    #[Route('/tache/nouvelle/{id_maintenance}', name: 'app_tache_new', defaults: ['id_maintenance' => null])]
public function new(
    Request $request,
    EntityManagerInterface $em,
    MaintenanceRepository $maintenanceRepository,
    MaintenancePlanningMailer $maintenancePlanningMailer,
    ?int $id_maintenance = null
): Response
{
    $tache = new Tache();

    // Vérification de la session utilisateur
    $sessionUser = $request->getSession()->get('user');
    if (!$sessionUser instanceof User) {
        return $this->redirectToRoute('front_login');
    }

    $technicien = $em->getRepository(User::class)->find($sessionUser->getId());
    if (!$technicien) {
        return $this->redirectToRoute('front_login');
    }

    $tache->setIdTechnicien($technicien);

    // Liaison avec la maintenance si présente
    if ($id_maintenance) {
        $maintenance = $maintenanceRepository->find($id_maintenance);
        if ($maintenance) {
            $tache->setIdMaintenance($maintenance);
        }
    }

    $tache->setDatePrevue(new \DateTime()); 
    $tache->setEvaluation(0);

    $form = $this->createForm(TacheType::class, $tache);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $maintenance = $tache->getIdMaintenance();
        if ($maintenance && $maintenance->getStatut() !== 'Planifiée' && $maintenance->getStatut() !== 'Résolue') {
            $maintenance->setStatut('Planifiée');
        }

        $em->persist($tache);
        $em->flush();

        // Envoi du mail et message de retour utilisateur
        try {
            $maintenancePlanningMailer->sendPlanningNotification($tache);
           
        } catch (\Throwable $e) {
            $this->addFlash('warning', 'La tâche est enregistrée, mais l\'email n\'a pas pu être envoyé.');
        }

        return $this->redirectToRoute('app_maintenance_taches', [
            'id_maintenance' => $maintenance ? $maintenance->getId_maintenance() : null,
        ]);
    }

    return $this->render('front/maintenance/new_tache.html.twig', [
        'form' => $form->createView(),
        'maintenanceId' => $id_maintenance,
    ]);
}

 #[Route('/tache/generer-description', name: 'app_tache_generate_description', methods: ['POST'])]
public function generateDescription(
    Request $request,
    MaintenanceRepository $maintenanceRepository,
    TaskDescriptionAiService $taskDescriptionAiService,
    EntityManagerInterface $em
): JsonResponse {

    $userId = $request->getSession()->get('user')?->getId();
    $sessionUser = $userId
        ? $em->getRepository(User::class)->find($userId)
        : null;

    if (!$sessionUser) {
        return new JsonResponse(['error' => 'Session invalide'], Response::HTTP_UNAUTHORIZED);
    }

    $payload = json_decode($request->getContent(), true) ?? [];

    $maintenanceId = (int)($payload['id_maintenance'] ?? 0);
    $taskName = trim($payload['nomTache'] ?? '');

    if ($maintenanceId <= 0) {
        return new JsonResponse(['error' => 'Maintenance ID invalide'], Response::HTTP_BAD_REQUEST);
    }

    $maintenance = $maintenanceRepository->find($maintenanceId);

    if (!$maintenance) {
        return new JsonResponse(['error' => 'Maintenance introuvable'], Response::HTTP_NOT_FOUND);
    }

    try {
        $description = $taskDescriptionAiService->generateForMaintenance(
            $maintenance,
            $taskName,
            $sessionUser
        );

        return new JsonResponse([
            'description' => $description
        ]);

    } catch (\Throwable $e) {
        return new JsonResponse([
            'error' => $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
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

    #[Route('/maintenance/{id_maintenance}/cloturer', name: 'app_maintenance_cloturer', methods: ['POST'])]
    public function cloturerMaintenance(Request $request, Maintenance $maintenance, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('cloturer'.$maintenance->getId_maintenance(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide.');
            return $this->redirectToRoute('app_maintenance_taches', [
                'id_maintenance' => $maintenance->getId_maintenance(),
            ]);
        }

        $maintenance->setStatut('Résolue');
        $em->flush();
       

        return $this->redirectToRoute('app_maintenance_taches', [
            'id_maintenance' => $maintenance->getId_maintenance(),
        ]);
    }
#[Route('/tache/vote/{id_tache}/{score}', name: 'app_tache_vote')]
    public function vote(
        int $id_tache, 
        int $score, 
        TacheRepository $repo, 
        EntityManagerInterface $em
    ): Response {
        $tache = $repo->find($id_tache);

        if (!$tache) {
            throw $this->createNotFoundException('Tâche non trouvée');
        }

        // On change l'évaluation
        if ($tache->getEvaluation() === $score) {
            $tache->setEvaluation(0);
        } else {
            $tache->setEvaluation($score);
        }

        $em->flush();

        // On redirige vers la page des tâches de la maintenance concernée
        return $this->redirectToRoute('app_maintenance_detail', [
            'id_maintenance' => $tache->getIdMaintenance()->getId_maintenance()
        ]);
    }

}