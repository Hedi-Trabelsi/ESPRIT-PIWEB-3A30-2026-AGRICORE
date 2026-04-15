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
use App\Service\MaintenanceDateChangeNotificationStore;
use App\Service\TaskDescriptionAiService;
use App\Service\TwilioSmsApiService;
class TacheController extends AbstractController
{
    private const DAILY_TASK_OVERLOAD_THRESHOLD = 4;

    #[Route('/tache/nouvelle/{id_maintenance}', name: 'app_tache_new', defaults: ['id_maintenance' => null])]
public function new(
    Request $request,
    EntityManagerInterface $em,
    MaintenanceRepository $maintenanceRepository,
    MaintenancePlanningMailer $maintenancePlanningMailer,
    TwilioSmsApiService $twilioSmsApiService,
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

        try {
            $twilioSmsApiService->sendTaskPlannedSms($tache);
        } catch (\Throwable) {
            $this->addFlash('warning', 'La tâche est enregistrée, mais le SMS Twilio n\'a pas pu être envoyé.');
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

    #[Route('/tache/jour-charge', name: 'app_tache_day_load', methods: ['GET'])]
    public function dayLoad(Request $request, EntityManagerInterface $em, TacheRepository $tacheRepository): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        $userId = null;
        $userRole = null;

        if ($sessionUser instanceof User) {
            $userId = $sessionUser->getId();
            $userRole = $sessionUser->getRole();
        } elseif (is_array($sessionUser)) {
            $userId = isset($sessionUser['id']) ? (int) $sessionUser['id'] : null;
            $userRole = isset($sessionUser['role']) ? (int) $sessionUser['role'] : null;
        }

        if (!$userId || (int) $userRole !== 2) {
            return new JsonResponse(['error' => 'Session invalide'], Response::HTTP_UNAUTHORIZED);
        }

        $dateValue = trim((string) $request->query->get('date', ''));
        if ($dateValue === '') {
            return new JsonResponse(['error' => 'Date manquante'], Response::HTTP_BAD_REQUEST);
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $dateValue);
        $dateErrors = \DateTimeImmutable::getLastErrors();
        if (!$date || ($dateErrors !== false && (($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0))) {
            return new JsonResponse(['error' => 'Date invalide'], Response::HTTP_BAD_REQUEST);
        }

        $technician = $em->getRepository(User::class)->find($userId);
        if (!$technician) {
            return new JsonResponse(['error' => 'Technicien introuvable'], Response::HTTP_NOT_FOUND);
        }

        $count = $tacheRepository->countTasksForTechnicianOnDate($technician->getId(), $date);

        return new JsonResponse([
            'date' => $date->format('Y-m-d'),
            'count' => $count,
            'threshold' => self::DAILY_TASK_OVERLOAD_THRESHOLD,
            'isOverloaded' => $count > self::DAILY_TASK_OVERLOAD_THRESHOLD,
            'message' => $count > self::DAILY_TASK_OVERLOAD_THRESHOLD
            ? sprintf('Ce jour est déjà surchargé: %d tâche%s planifiée%s.', $count, $count > 1 ? 's' : '', $count > 1 ? 's' : '')
            : sprintf('Ce jour contient actuellement %d tâche%s planifiée%s.', $count, $count > 1 ? 's' : '', $count > 1 ? 's' : ''),
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
    public function edit(Tache $tache, Request $request, EntityManagerInterface $em, MaintenanceDateChangeNotificationStore $notificationStore): Response
    {
        $sessionUser = $request->getSession()->get('user');

        $originalDatePrevue = $tache->getDatePrevue() instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($tache->getDatePrevue())
            : null;

        $form = $this->createForm(TacheType::class, $tache);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newDatePrevue = $tache->getDatePrevue() instanceof \DateTimeInterface
                ? \DateTimeImmutable::createFromInterface($tache->getDatePrevue())
                : null;

            if ($originalDatePrevue?->format('Y-m-d') !== $newDatePrevue?->format('Y-m-d')) {
                $maintenance = $tache->getIdMaintenance();
                $owner = $maintenance?->getId_agriculteur();

                if ($maintenance && $owner && $owner->getId() !== null) {
                    $technicianName = $sessionUser instanceof User
                        ? trim(($sessionUser->getPrenom() ?? '') . ' ' . ($sessionUser->getNom() ?? ''))
                        : null;

                    $notificationStore->addChangeNotification([
                        'id' => uniqid('maintenance_date_change_', true),
                        'farmer_id' => (int) $owner->getId(),
                        'maintenance_id' => $maintenance->getId_maintenance(),
                        'maintenance_name' => $maintenance->getNomMaintenance(),
                        'task_id' => $tache->getId_tache(),
                        'task_name' => $tache->getNomTache(),
                        'previous_date' => $originalDatePrevue?->format('d/m/Y'),
                        'new_date' => $newDatePrevue?->format('d/m/Y'),
                        'technician_name' => $technicianName !== '' ? $technicianName : null,
                        'seen_at' => null,
                        'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                    ]);
                }
            }

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

    #[Route('/tache/terminer/{id_tache}', name: 'app_tache_complete', methods: ['POST'])]
    public function completeTask(Request $request, Tache $tache, EntityManagerInterface $em): Response
    {
        $maintenanceId = $tache->getIdMaintenance()->getId_maintenance();

        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser instanceof User || $sessionUser->getRole() !== 2) {
            return $this->redirectToRoute('front_login');
        }

        if (!$this->isCsrfTokenValid('complete'.$tache->getId_tache(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Action invalide.');
            return $this->redirectToRoute('app_maintenance_taches', [
                'id_maintenance' => $maintenanceId,
            ]);
        }

        // Le technicien assigné peut marquer la tâche comme terminée.
        if ($tache->getIdTechnicien() && $tache->getIdTechnicien()->getId() !== $sessionUserId) {
            $this->addFlash('danger', 'Vous ne pouvez pas modifier cette tâche.');
            return $this->redirectToRoute('app_maintenance_taches', [
                'id_maintenance' => $maintenanceId,
            ]);
        }

        $tache->setEtat(1);
        $em->flush();

      

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