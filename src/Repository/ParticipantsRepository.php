<?php

namespace App\Repository;

use App\Entity\Participants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ParticipantsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participants::class);
    }

    // =========================
    // COUNT TOTAL PLACES FOR AN EVENT
    // =========================
    public function countPlacesByEvent(int $eventId): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('SUM(p.nbr_places) as total')
            ->where('p.evenement = :ev')
            ->andWhere('p.statut_participation != :waitlist')
            ->setParameter('ev', $eventId)
            ->setParameter('waitlist', 'waitlist');

        $result = $qb->getQuery()->getSingleScalarResult();
        return (int)$result;
    }
}
