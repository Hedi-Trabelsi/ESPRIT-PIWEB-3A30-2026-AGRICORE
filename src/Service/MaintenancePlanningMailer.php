<?php

namespace App\Service;

use App\Entity\Tache;
use Dompdf\Dompdf;
use Dompdf\Options;
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

        $agriculteur = $maintenance->getId_agriculteur();
        $technicien = $tache->getIdTechnicien();

        $logoPath = $this->getLogoPath();
        $pdfLogoPath = extension_loaded('gd') ? $logoPath : null;
        $logoCid = null;

        $templateData = [
            'maintenance' => $maintenance,
            'tache' => $tache,
            'agriculteur' => $agriculteur,
            'technicien' => $technicien,
            'sentAt' => new \DateTimeImmutable(),
            'logoCid' => $logoCid,
            'logoFilePath' => $pdfLogoPath,
        ];

        $textBody = $this->twig->render('emails/maintenance_planifiee.txt.twig', $templateData);

        $email = (new Email())
            ->from($this->fromAddress)
            ->to($recipientEmail)
            ->subject('Planification de votre maintenance')
            ->text($textBody);

        if ($logoPath !== null) {
            $email->embedFromPath($logoPath, 'agricore-logo', 'image/png');
            $templateData['logoCid'] = 'cid:agricore-logo';
        }

        $htmlBody = $this->twig->render('emails/maintenance_planifiee.html.twig', $templateData);
        $email->html($htmlBody);

        try {
            $pdfBody = $this->twig->render('emails/maintenance_planifiee.pdf.twig', $templateData);

            $pdfOptions = new Options();
            $pdfOptions->set('defaultFont', 'DejaVu Sans');
            $pdfOptions->setChroot(dirname(__DIR__, 2));
            $pdf = new Dompdf($pdfOptions);
            $pdf->loadHtml($pdfBody);
            $pdf->setPaper('A4', 'portrait');
            $pdf->render();

            $pdfFilename = sprintf(
                'rappel-maintenance-%d-tache-%d-%s.pdf',
                $maintenance->getId_maintenance(),
                $tache->getId_tache(),
                (new \DateTimeImmutable())->format('Ymd-His')
            );

            $pdfContent = $pdf->output();
            $savedPath = $this->savePdfCopy($pdfFilename, $pdfContent);
            if ($savedPath !== null) {
                $this->logger->info('Maintenance PDF saved locally.', [
                    'path' => $savedPath,
                    'maintenanceId' => $maintenance->getId_maintenance(),
                    'tacheId' => $tache->getId_tache(),
                ]);
            }

            $email->attach($pdfContent, $pdfFilename, 'application/pdf');
        } catch (\Throwable $e) {
            $this->logger->warning('Maintenance planning PDF attachment generation failed. Sending email without attachment.', [
                'maintenanceId' => $maintenance->getId_maintenance(),
                'tacheId' => $tache->getId_tache(),
                'error' => $e->getMessage(),
            ]);
        }

        $this->mailer->send($email);
    }

    private function getLogoPath(): ?string
    {
        $logoPath = dirname(__DIR__, 2).'/public/img/logo.png';
        if (!is_file($logoPath)) {
            return null;
        }

        return $logoPath;
    }

    private function savePdfCopy(string $pdfFilename, string $pdfContent): ?string
    {
        $targetDir = dirname(__DIR__, 2).'/public/uploads/maintenance_pdfs';
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            return null;
        }

        $path = $targetDir.'/'.$pdfFilename;
        $written = @file_put_contents($path, $pdfContent);
        if ($written === false) {
            return null;
        }

        return $path;
    }

}

