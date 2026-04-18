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

    public function sendAnomalyAlert(User $user, Depense $depense, array $analysis): bool
    {
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

            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface $e) {
            // Log error or handle it
            return false;
        }
    }
}
