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
    public function findByFilters(?string $search, ?string $status, ?string $priority): array
    {
        $qb = $this->createQueryBuilder('m');

        // 1. Filtre par texte (nom, équipement ou lieu)
        if ($search) {
            $qb->andWhere('m.nom_maintenance LIKE :val OR m.equipement LIKE :val OR m.lieu LIKE :val')
               ->setParameter('val', '%' . $search . '%');
        }

        // 2. Filtre par statut
        if ($status && $status !== 'all') {
            $qb->andWhere('m.statut = :status')
               ->setParameter('status', $status);
        }

        // 3. Filtre par priorité (Ajouté ici !)
        if ($priority) {
            $qb->andWhere('m.priorite = :priority')
               ->setParameter('priority', $priority);
        }

        // Tri par date la plus récente
        return $qb->orderBy('m.date_declaration', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}