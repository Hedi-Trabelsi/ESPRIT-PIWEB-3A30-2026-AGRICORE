<?php

namespace App\Repository;

use App\Entity\Tache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tache::class);
    }

    public function findByTechnicianAndDateRange(int $technicianId, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.id_technicien', 'u')
            ->innerJoin('t.id_maintenance', 'm')
            ->addSelect('m')
            ->andWhere('u.id = :technicianId')
            ->andWhere('t.date_prevue BETWEEN :start AND :end')
            ->setParameter('technicianId', $technicianId)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('t.date_prevue', 'ASC')
            ->addOrderBy('t.nomTache', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueTasksForTechnician(int $technicianId, \DateTimeInterface $today): array
    {
        return $this->createQueryBuilder('t')
            ->innerJoin('t.id_technicien', 'u')
            ->innerJoin('t.id_maintenance', 'm')
            ->addSelect('m')
            ->andWhere('u.id = :technicianId')
            ->andWhere('t.date_prevue < :today')
            ->andWhere('m.statut NOT IN (:resolvedStatuses)')
            ->setParameter('technicianId', $technicianId)
            ->setParameter('today', $today)
            ->setParameter('resolvedStatuses', ['Résolu', 'Résolue'])
            ->orderBy('t.date_prevue', 'ASC')
            ->addOrderBy('t.nomTache', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveTasksForTechnicianOnDate(int $technicianId, \DateTimeInterface $date, ?int $excludeTaskId = null): int
    {
        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id_tache)')
            ->innerJoin('t.id_technicien', 'u')
            ->andWhere('u.id = :technicianId')
            ->andWhere('t.date_prevue = :date')
            ->andWhere('t.etat IS NULL OR t.etat != :completedEtat')
            ->setParameter('technicianId', $technicianId)
            ->setParameter('date', $date)
            ->setParameter('completedEtat', 1);

        if ($excludeTaskId !== null) {
            $qb
                ->andWhere('t.id_tache != :excludeTaskId')
                ->setParameter('excludeTaskId', $excludeTaskId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
    
}
