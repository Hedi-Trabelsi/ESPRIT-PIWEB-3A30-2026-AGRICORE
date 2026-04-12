<?php

namespace App\Service;

use App\Entity\Tache;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TwilioSmsApiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        #[Autowire('%env(TWILIO_ACCOUNT_SID)%')]
        private readonly string $accountSid,
        #[Autowire('%env(TWILIO_AUTH_TOKEN)%')]
        private readonly string $authToken,
        #[Autowire('%env(TWILIO_FROM_NUMBER)%')]
        private readonly string $fromNumber
    ) {
    }

    public function sendTaskPlannedSms(Tache $tache): void
    {
        if (!$this->accountSid || !$this->authToken || !$this->fromNumber) {
            $this->logger->warning('Twilio SMS skipped: missing credentials.');

            return;
        }

        $technicien = $tache->getIdTechnicien();
        $maintenance = $tache->getIdMaintenance();
        $rawPhone = $technicien?->getNumeroT();

        if (!$technicien || !$maintenance || !$rawPhone) {
            $this->logger->warning('Twilio SMS skipped: missing technician/maintenance/phone.', [
                'tacheId' => $tache->getId_tache(),
            ]);

            return;
        }

        $to = $this->normalizePhone((string) $rawPhone);
        if (!$to) {
            $this->logger->warning('Twilio SMS skipped: invalid technician phone number.', [
                'phone' => (string) $rawPhone,
                'tacheId' => $tache->getId_tache(),
            ]);

            return;
        }

        $techName = trim(($technicien->getPrenom() ?? '') . ' ' . ($technicien->getNom() ?? ''));
        if ($techName === '') {
            $techName = 'Technicien';
        }

       $message = sprintf(
    "Bonjour %s,\n\n" .
    "Une nouvelle intervention a été enregistrée pour l’équipement : %s.\n\n" .
    "Date prévue : %s.\n\n" .
    "Vous êtes enregistré comme technicien responsable de cette intervention.",
    $techName,
    $maintenance->getEquipement(),
    $tache->getDatePrevue()?->format('Y-m-d') ?? 'non définie'
);

        $url = sprintf(
            'https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json',
            urlencode($this->accountSid)
        );

        $response = $this->httpClient->request('POST', $url, [
            'auth_basic' => [$this->accountSid, $this->authToken],
            'body' => [
                'To' => $to,
                'From' => $this->fromNumber,
                'Body' => $message,
            ],
            'timeout' => 10,
        ]);

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $this->logger->warning('Twilio SMS API returned non-success status.', [
                'status' => $status,
                'tacheId' => $tache->getId_tache(),
            ]);
        }
    }

    private function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($phone, '+')) {
            return '+' . $digits;
        }

      
        if (strlen($digits) === 8) {
            return '+216' . $digits;
        }

        if (strlen($digits) >= 10) {
            return '+' . $digits;
        }

        return null;
    }
}
