<?php

namespace App\EventSubscriber;

use App\Repository\DepenseRepository;
use App\Repository\VenteRepository;
use CalendarBundle\Entity\Event;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CalendarSubscriber implements EventSubscriberInterface
{
    private $venteRepository;
    private $depenseRepository;
    private $router;

    public function __construct(
        VenteRepository $venteRepository,
        DepenseRepository $depenseRepository,
        UrlGeneratorInterface $router
    ) {
        $this->venteRepository = $venteRepository;
        $this->depenseRepository = $depenseRepository;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar)
    {
        $start = $calendar->getStart();
        $end = $calendar->getEnd();
        $filters = $calendar->getFilters();

        // Check if we have a specific user ID in filters
        $userId = isset($filters['userId']) ? (int)$filters['userId'] : null;

        // Fetch Ventes
        $ventes = $userId 
            ? $this->venteRepository->createQueryBuilder('v')
                ->where('v.user = :userId')
                ->andWhere('v.date BETWEEN :start AND :end')
                ->setParameter('userId', $userId)
                ->setParameter('start', $start->format('Y-m-d H:i:s'))
                ->setParameter('end', $end->format('Y-m-d H:i:s'))
                ->getQuery()->getResult()
            : $this->venteRepository->findAll();

        foreach ($ventes as $vente) {
            $venteEvent = new Event(
                'Vente: ' . $vente->getProduit() . ' (' . $vente->getChiffreAffaires() . ' DT)',
                $vente->getDate()
            );

            $venteEvent->setOptions([
                'backgroundColor' => '#38a169',
                'borderColor' => '#38a169',
                'textColor' => 'white',
            ]);

            $calendar->addEvent($venteEvent);
        }

        // Fetch Depenses
        $depenses = $userId 
            ? $this->depenseRepository->createQueryBuilder('d')
                ->where('d.user = :userId')
                ->andWhere('d.date BETWEEN :start AND :end')
                ->setParameter('userId', $userId)
                ->setParameter('start', $start->format('Y-m-d H:i:s'))
                ->setParameter('end', $end->format('Y-m-d H:i:s'))
                ->getQuery()->getResult()
            : $this->depenseRepository->findAll();

        foreach ($depenses as $depense) {
            $depenseEvent = new Event(
                'Dépense: ' . $depense->getType() . ' (' . $depense->getMontant() . ' DT)',
                $depense->getDate()
            );

            $depenseEvent->setOptions([
                'backgroundColor' => '#e53e3e',
                'borderColor' => '#e53e3e',
                'textColor' => 'white',
            ]);

            $calendar->addEvent($depenseEvent);
        }

        // Add requested reminders
        $reminders = [
            ['title' => 'acheter des graines', 'date' => (new \DateTime())->modify('+1 day'), 'color' => '#3182ce'],
            ['title' => 'vendre des récoltes', 'date' => (new \DateTime())->modify('+3 days'), 'color' => '#d69e2e'],
        ];

        foreach ($reminders as $reminder) {
            $reminderEvent = new Event(
                'Reminder: ' . $reminder['title'],
                $reminder['date']
            );

            $reminderEvent->setOptions([
                'backgroundColor' => $reminder['color'],
                'borderColor' => $reminder['color'],
                'textColor' => 'white',
            ]);

            $calendar->addEvent($reminderEvent);
        }
    }
}
