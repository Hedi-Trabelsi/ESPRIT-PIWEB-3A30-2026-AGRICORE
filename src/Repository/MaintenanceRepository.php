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
     */
  public function findByFilters(?string $search, ?string $status, $userId)
{
    $qb = $this->createQueryBuilder('m');

    if ($search) {
        $qb->andWhere('m.description LIKE :search')
           ->setParameter('search', '%' . $search . '%');
    }

    if ($status) {
        $qb->andWhere('m.statut = :status')
           ->setParameter('status', $status);
    }

    // 🔥 AJOUT IMPORTANT
    if ($userId) {
        $qb->andWhere('m.id_agriculteur = :userId')
           ->setParameter('userId', $userId);
    }

    return $qb->getQuery()->getResult();
}
}
