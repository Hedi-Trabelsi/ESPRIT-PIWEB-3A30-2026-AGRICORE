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
    // src/Repository/MaintenanceRepository.php

public function findBySearchAndStatus(?string $search, ?string $status): array
{
    $qb = $this->createQueryBuilder('m');

    // Filtre par texte (nom, équipement ou lieu)
    if ($search) {
        $qb->andWhere('m.nom_maintenance LIKE :val OR m.equipement LIKE :val OR m.lieu LIKE :val')
           ->setParameter('val', '%' . $search . '%');
    }

    // Filtre par statut
    if ($status && $status !== 'all') {
        $qb->andWhere('m.statut = :status')
           ->setParameter('status', $status);
    }

    return $qb->orderBy('m.date_declaration', 'DESC')
              ->getQuery()
              ->getResult();
}
}