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

    public function countTasksForTechnicianOnDate(int $technicianId, \DateTimeInterface $date, ?int $excludeTaskId = null): int
    {
        $startOfDay = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0, 0);
        $endOfDay = \DateTimeImmutable::createFromInterface($date)->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('t')
            ->select('COUNT(t.id_tache)')
            ->innerJoin('t.id_technicien', 'u')
            ->andWhere('u.id = :technicianId')
            ->andWhere('t.date_prevue BETWEEN :startOfDay AND :endOfDay')
            ->setParameter('technicianId', $technicianId)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay);

        if ($excludeTaskId !== null) {
            $qb
                ->andWhere('t.id_tache != :excludeTaskId')
                ->setParameter('excludeTaskId', $excludeTaskId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function getTechniciansWithNegativeEvaluations(): array
    {
        return $this->createQueryBuilder('t')
            ->select('u.id, u.nom, u.prenom, COUNT(t.id_tache) as negativeCount')
            ->innerJoin('t.id_technicien', 'u')
            ->andWhere('t.evaluation = :negativeEval')
            ->setParameter('negativeEval', -1)
            ->groupBy('u.id')
            ->orderBy('negativeCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    public function getTechniciansWithPositiveEvaluations(): array
{
    return $this->createQueryBuilder('t')
        ->select('u.id, u.nom, u.prenom, COUNT(t.id_tache) as positiveCount')
        ->innerJoin('t.id_technicien', 'u')
        ->andWhere('t.evaluation = :positiveEval') // Ajustez la valeur selon votre base
        ->setParameter('positiveEval', 1) 
        ->groupBy('u.id')
        ->orderBy('positiveCount', 'DESC')
        ->getQuery()
        ->getResult();
}
}
