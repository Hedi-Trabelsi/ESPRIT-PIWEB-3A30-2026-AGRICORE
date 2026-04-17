<?php

namespace App\Repository;

use App\Entity\SuiviAnimal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SuiviAnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviAnimal::class);
    }

    public function findByAnimalAndPeriode(\App\Entity\Animal $animal, string $dateDebut, string $dateFin): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.animal = :animal')
            ->andWhere('s.dateSuivi >= :debut')
            ->andWhere('s.dateSuivi <= :fin')
            ->setParameter('animal', $animal)
            ->setParameter('debut', new \DateTime($dateDebut.' 00:00:00'))
            ->setParameter('fin',   new \DateTime($dateFin.' 23:59:59'))
            ->orderBy('s.dateSuivi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $q = '', string $sortBy = 'dateSuivi', string $order = 'DESC', ?int $idAgriculteur = null): array    {
        $allowed = ['dateSuivi', 'temperature', 'poids', 'rythmeCardiaque', 'etatSante', 'niveauActivite'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'dateSuivi';
        $order   = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.animal', 'a');

        if ($idAgriculteur !== null) {
            $qb->andWhere('a.idAgriculteur = :agriculteur')->setParameter('agriculteur', $idAgriculteur);
        }

        if ($q !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.codeAnimal',     ':q'),
                    $qb->expr()->like('s.etatSante',      ':q'),
                    $qb->expr()->like('s.niveauActivite', ':q'),
                    $qb->expr()->like('s.remarque',       ':q')
                )
            )->setParameter('q', '%'.$q.'%');
        }

        return $qb->orderBy('s.'.$sortBy, $order)->getQuery()->getResult();
    }

    public function searchStatic(
        string $etatSante      = '',
        string $niveauActivite = '',
        ?float $tempMin        = null,
        ?float $tempMax        = null,
        string $sortBy         = 'dateSuivi',
        string $order          = 'DESC',
        ?int   $idAgriculteur  = null
    ): array {
        $allowed = ['dateSuivi', 'temperature', 'poids', 'rythmeCardiaque', 'etatSante', 'niveauActivite'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'dateSuivi';
        $order   = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.animal', 'a');

        if ($idAgriculteur !== null) {
            $qb->andWhere('a.idAgriculteur = :agriculteur')->setParameter('agriculteur', $idAgriculteur);
        }

        if ($etatSante !== '') {
            $qb->andWhere('s.etatSante = :etat')->setParameter('etat', $etatSante);
        }
        if ($niveauActivite !== '') {
            $qb->andWhere('s.niveauActivite = :activite')->setParameter('activite', $niveauActivite);
        }
        if ($tempMin !== null) {
            $qb->andWhere('s.temperature >= :tmin')->setParameter('tmin', $tempMin);
        }
        if ($tempMax !== null) {
            $qb->andWhere('s.temperature <= :tmax')->setParameter('tmax', $tempMax);
        }

        return $qb->orderBy('s.'.$sortBy, $order)->getQuery()->getResult();
    }
}
