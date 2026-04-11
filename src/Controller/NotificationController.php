<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Entity\Participants;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NotificationController extends AbstractController
{
    #[Route('/notifications/events', name: 'front_notifications_events', methods: ['GET'])]
    public function upcomingEvents(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $request->getSession()->get('user');
        if (!$user) return new JsonResponse([]);

        $now   = new \DateTime();
        $in24h = (clone $now)->modify('+24 hours');

        $participations = $em->getRepository(Participants::class)->findBy([
            'id_utilisateur' => $user->getId()
        ]);

        $notifications = [];
        foreach ($participations as $p) {
            $ev = $p->getEvenement();
            if (!$ev) continue;

            $start = $ev->getDateDebut();
            if (!$start) continue;

            if ($start > $now && $start <= $in24h) {
                $diff  = $now->diff($start);
                $hours = ($diff->days * 24) + $diff->h;
                $mins  = $diff->i;

                $timeLabel = $hours > 0
                    ? "dans {$hours}h" . ($mins > 0 ? "{$mins}min" : '')
                    : "dans {$mins} min";

                $notifications[] = [
                    'id'        => $ev->getIdEv(),
                    'titre'     => $ev->getTitre(),
                    'lieu'      => $ev->getLieu(),
                    'start'     => $start->format('d/m/Y H:i'),
                    'timeLabel' => $timeLabel,
                    'url'       => '/evenement/' . $ev->getIdEv(),
                ];
            }
        }

        return new JsonResponse($notifications);
    }
}
