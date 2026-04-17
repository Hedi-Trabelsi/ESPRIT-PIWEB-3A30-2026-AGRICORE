<?php

namespace App\Controller;

use App\Entity\Tache;
use App\Service\TwilioSmsApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MaintenanceSmsController extends AbstractController
{
    #[Route('/api/tache/{id_tache}/sms-technicien', name: 'api_tache_sms_technicien', methods: ['POST'])]
    public function sendSmsToTechnician(
        Request $request,
        Tache $tache,
        TwilioSmsApiService $twilioSmsApiService,
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
            $twilioSmsApiService->sendTaskPlannedSms($managedTask);

            return new JsonResponse([
                'success' => true,
                'message' => 'SMS envoyé au technicien via Twilio API.',
            ]);
        } catch (\Throwable) {
            return new JsonResponse([
                'success' => false,
                'error' => "Erreur lors de l'envoi du SMS via Twilio.",
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
