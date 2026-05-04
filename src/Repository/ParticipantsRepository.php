<?php

namespace App\Repository;

use App\Entity\Participants;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participants>
 */
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

    /**
     * Fetch participations for a user with the related Evenement eagerly loaded.
     * Avoids N+1 lazy loading when iterating to build calendar events.
     *
     * @return Participants[]
     */
    public function findWithEvenementByUser(int $userId): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p', 'e')
            ->leftJoin('p.evenement', 'e')
            ->where('p.id_utilisateur = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getResult();
        
        /** @var Participants[] $result */
        return $result ?: [];
    }
}
