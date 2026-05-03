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

            // ── 24h reminder for confirmed participants ──
            if ($p->getStatutParticipation() !== 'waitlist') {
                $start = $ev->getDateDebut();
                if ($start && $start > $now && $start <= $in24h) {
                    $diff  = $now->diff($start);
                    $hours = ($diff->days * 24) + $diff->h;
                    $mins  = $diff->i;
                    $timeLabel = $hours > 0
                        ? "dans {$hours}h" . ($mins > 0 ? "{$mins}min" : '')
                        : "dans {$mins} min";

                    $notifications[] = [
                        'id'        => $ev->getIdEv(),
                        'type'      => 'reminder',
                        'titre'     => $ev->getTitre(),
                        'lieu'      => $ev->getLieu(),
                        'start'     => $start->format('d/m/Y H:i'),
                        'timeLabel' => $timeLabel,
                        'url'       => '/evenement/' . $ev->getIdEv(),
                    ];
                }
            }

            // ── Waitlist spot available notification ──
            if ($p->getStatutParticipation() === 'waitlist') {
                // Count real reserved places (excluding waitlist)
                $reserved = (int) $em->getRepository(Participants::class)
                    ->createQueryBuilder('p2')
                    ->select('SUM(p2.nbr_places)')
                    ->where('p2.evenement = :ev')
                    ->andWhere('p2.statut_participation != :ws')
                    ->setParameter('ev', $ev)
                    ->setParameter('ws', 'waitlist')
                    ->getQuery()->getSingleScalarResult() ?: 0;

                $placesLibres = $ev->getCapaciteMax() - $reserved;

                if ($placesLibres > 0) {
                    $notifications[] = [
                        'id'          => $ev->getIdEv(),
                        'type'        => 'waitlist',
                        'titre'       => $ev->getTitre(),
                        'lieu'        => $ev->getLieu(),
                        'start'       => $ev->getDateDebut()?->format('d/m/Y H:i'),
                        'placesLibres'=> $placesLibres,
                        'timeLabel'   => $placesLibres . ' place' . ($placesLibres > 1 ? 's' : '') . ' disponible' . ($placesLibres > 1 ? 's' : ''),
                        'url'         => '/evenement/' . $ev->getIdEv(),
                    ];
                }
            }
        }

        return new JsonResponse($notifications);
    }
}
