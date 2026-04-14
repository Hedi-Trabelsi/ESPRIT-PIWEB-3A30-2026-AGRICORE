<?php

namespace App\EventSubscriber;

use App\Entity\Evennementagricole;
use App\Entity\Participants;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private RouterInterface $router
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendarEvent): void
    {
        $start = $calendarEvent->getStart();
        $end   = $calendarEvent->getEnd();

        $evenements = $this->em->getRepository(Evennementagricole::class)
            ->createQueryBuilder('e')
            ->where('e.date_debut <= :end AND e.date_fin >= :start')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();

        $now = new \DateTime();

        foreach ($evenements as $ev) {
            if ($ev->getDateFin() < $now) {
                $color = '#94a3b8';
            } elseif ($ev->getDateDebut() > $now) {
                $color = '#3b82f6';
            } else {
                $color = '#16a34a';
            }

            $reserved = (int) $this->em->getRepository(Participants::class)
                ->createQueryBuilder('p')
                ->select('SUM(p.nbr_places)')
                ->where('p.evenement = :ev')
                ->setParameter('ev', $ev)
                ->getQuery()
                ->getSingleScalarResult() ?: 0;

            $places = max(0, $ev->getCapaciteMax() - $reserved);

            $event = new Event(
                $ev->getTitre() . ' — ' . $places . ' places',
                $ev->getDateDebut(),
                $ev->getDateFin()
            );

            $event->setOptions([
                'color'       => $color,
                'url'         => $this->router->generate('app_evenement_show', ['id' => $ev->getIdEv()]),
                'borderColor' => $color,
                'extendedProps' => [
                    'lieu'   => $ev->getLieu(),
                    'prix'   => $ev->getFraisInscription() . ' DT',
                    'places' => $places,
                ],
            ]);

            $calendarEvent->addEvent($event);
        }
    }
}
