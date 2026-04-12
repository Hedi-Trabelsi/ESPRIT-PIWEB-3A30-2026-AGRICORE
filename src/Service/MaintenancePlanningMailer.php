<?php

namespace App\Service;

use App\Entity\Tache;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class MaintenancePlanningMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
        // Dans ton Service
private readonly string $fromAddress = 'AgriCore Support <mrabetzeineb1@gmail.com>'
    ) {
    }

    public function sendPlanningNotification(Tache $tache): void
    {
        $maintenance = $tache->getIdMaintenance();
        $agriculteur = $maintenance?->getId_agriculteur();
        $recipientEmail = $agriculteur?->getEmail();

        if (!$maintenance || !$recipientEmail) {
            $this->logger->warning('Planning email skipped because recipient or maintenance is missing.', [
                'maintenanceId' => $maintenance?->getId_maintenance(),
                'tacheId' => $tache->getId_tache(),
            ]);

            return;
        }

        $body = $this->twig->render('emails/maintenance_planifiee.txt.twig', [
            'maintenance' => $maintenance,
            'tache' => $tache,
        ]);

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($recipientEmail)
            ->subject('Planification de votre maintenance')
            ->text($body);

        $this->mailer->send($email);
    }
}

