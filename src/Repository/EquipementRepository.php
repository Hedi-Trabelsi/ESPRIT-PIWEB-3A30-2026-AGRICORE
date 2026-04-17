<?php

namespace App\Repository;

use App\Entity\Equipement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipement>
 */
class EquipementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipement::class);
    }

    /**
     * @return Equipement[]
     */
    public function findActiveBySearch(string $search = ''): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.isActive = true')
            ->orderBy('e.id_equipement', 'DESC');

        if ($search !== '') {
            $qb->andWhere('e.nom LIKE :search OR e.type LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return Equipement[]
     */
    public function findRelatedActive(Equipement $equipement, int $limit = 4): array
    {
        $type = trim((string) $equipement->getType());

        if ($type === '') {
            return [];
        }

        return $this->createQueryBuilder('e')
            ->andWhere('e.isActive = true')
            ->andWhere('e.id_equipement != :id')
            ->andWhere('e.type = :type')
            ->setParameter('id', $equipement->getId())
            ->setParameter('type', $type)
            ->orderBy('e.quantite', 'DESC')
            ->addOrderBy('e.id_equipement', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getCatalogueStats(): array
    {
        $active = $this->findBy(['isActive' => true]);
        $totalValue = 0.0;
        $totalStock = 0;
        $lowStock = 0;
        $outOfStock = 0;
        $byType = [];

        foreach ($active as $equipement) {
            $qty = (int) $equipement->getQuantite();
            $price = (float) $equipement->getPrix();
            $type = $equipement->getType() ?: 'Non classe';
            $totalStock += $qty;
            $totalValue += $price * $qty;

            if ($qty === 0) {
                ++$outOfStock;
            } elseif ($qty < 5) {
                ++$lowStock;
            }

            if (!isset($byType[$type])) {
                $byType[$type] = [
                    'type' => $type,
                    'total' => 0,
                    'stock' => 0,
                    'value' => 0.0,
                ];
            }

            ++$byType[$type]['total'];
            $byType[$type]['stock'] += $qty;
            $byType[$type]['value'] += $price * $qty;
        }

        usort($byType, static fn (array $left, array $right): int => $right['stock'] <=> $left['stock']);

        return [
            'totals' => [
                'equipements' => count($active),
                'stock' => $totalStock,
                'value' => $totalValue,
                'low_stock' => $lowStock,
                'out_of_stock' => $outOfStock,
            ],
            'by_type' => array_values($byType),
        ];
    }
}
