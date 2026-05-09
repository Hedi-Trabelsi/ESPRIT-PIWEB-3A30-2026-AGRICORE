<?php

namespace App\Repository;

use App\Entity\Maintenance;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Maintenance>
 */
class MaintenanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Maintenance::class);
    }

    /**
     * Cette méthode doit correspondre exactement au nom appelé dans ton Controller
     * @return list<Maintenance>
     */
    public function findByFilters(?string $search, ?string $status, ?int $userId): array
    {
        $qb = $this->createQueryBuilder('m');

        if ($search) {
            $qb->andWhere('m.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($status) {
            $acceptedStatuses = ['accepter', 'Acceptée', 'Acceptee', 'Accepté', 'Accepte', 'Accepter'];

            if (in_array($status, $acceptedStatuses, true)) {
                $qb->andWhere('m.statut IN (:acceptedStatuses)')
                    ->setParameter('acceptedStatuses', $acceptedStatuses);
            } else {
                $qb->andWhere('m.statut = :status')
                    ->setParameter('status', $status);
            }
        }

        if ($userId) {
            $qb->andWhere('m.id_agriculteur = :userId')
                ->setParameter('userId', $userId);
        }

        $result = $qb->getQuery()->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof Maintenance)) : [];
    }

    /**
     * Fetch maintenances for an agriculteur with the given statut and their taches eagerly loaded.
     * Avoids N+1 lazy loading on $maintenance->getTaches() in calendar building.
     *
     * @return Maintenance[]
     */
    public function findWithTachesByAgriculteurAndStatut(int $agriculteurId, string $statut): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('m', 't')
            ->leftJoin('m.taches', 't')
            ->where('m.id_agriculteur = :uid')
            ->andWhere('m.statut = :statut')
            ->setParameter('uid', $agriculteurId)
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof Maintenance)) : [];
    }
}
