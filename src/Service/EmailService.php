<?php

namespace App\Service;

use App\Entity\Depense;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public string $lastError = '';

    public function sendAnomalyAlert(User $user, Depense $depense, array $analysis): bool
    {
        $this->lastError = '';
        try {
            $email = (new TemplatedEmail())
                ->from('ncibifiras19@gmail.com')
                ->to($user->getEmail())
                ->subject('Alerte Anomalie - Agricore')
                ->htmlTemplate('emails/anomaly_alert.html.twig')
                ->context([
                    'user' => $user,
                    'depense' => $depense,
                    'analysis' => $analysis,
                ]);

            // Route this email through the "finance" transport (ncibifiras) instead of the main one.
            $email->getHeaders()->addTextHeader('X-Transport', 'finance');

            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->lastError = $e->getMessage();
            return false;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }
}
