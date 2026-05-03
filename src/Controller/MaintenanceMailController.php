<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Service\MaintenancePlanningMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MaintenanceMailController extends AbstractController
{
    #[Route('/maintenance/{id_tache}/mail-planification', name: 'app_maintenance_mail_planification', methods: ['POST'])]
    public function sendPlanningMail(
        Request $request,
        Tache $tache,
        MaintenancePlanningMailer $maintenancePlanningMailer,
        EntityManagerInterface $em
    ): JsonResponse {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return new JsonResponse(['success' => false, 'error' => 'Session invalide'], Response::HTTP_UNAUTHORIZED);
        }

        $managedTask = $em->getRepository(Tache::class)->find($tache->getId_tache());
        if (!$managedTask) {
            return new JsonResponse(['success' => false, 'error' => 'Tâche introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $maintenancePlanningMailer->sendPlanningNotification($managedTask);

            return new JsonResponse([
                'success' => true,
                'message' => 'Mail envoyé avec succès.',
            ]);
        } catch (\Throwable) {
            return new JsonResponse([
                'success' => false,
                'error' => "Erreur lors de l'envoi du mail.",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
