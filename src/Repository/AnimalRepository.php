<?php

namespace App\Repository;

use App\Entity\Animal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Animal::class);
    }

    public function search(string $q = '', string $sortBy = 'codeAnimal', string $order = 'ASC', ?int $idAgriculteur = null): array
    {
        $allowed = ['codeAnimal', 'espece', 'race', 'sexe', 'dateNaissance'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'codeAnimal';
        $order   = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('a');

        if ($idAgriculteur !== null) {
            $qb->andWhere('a.idAgriculteur = :agriculteur')->setParameter('agriculteur', $idAgriculteur);
        }

        if ($q !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.codeAnimal', ':q'),
                    $qb->expr()->like('a.espece',     ':q'),
                    $qb->expr()->like('a.race',       ':q'),
                    $qb->expr()->like('a.sexe',       ':q')
                )
            )->setParameter('q', '%'.$q.'%');
        }

        return $qb->orderBy('a.'.$sortBy, $order)->getQuery()->getResult();
    }

    public function searchStatic(
        string $codeAnimal = '',
        string $espece     = '',
        string $race       = '',
        string $sexe       = '',
        string $sortBy     = 'codeAnimal',
        string $order      = 'ASC',
        ?int   $idAgriculteur = null
    ): array {
        $allowed = ['codeAnimal', 'espece', 'race', 'sexe', 'dateNaissance'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'codeAnimal';
        $order   = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $this->createQueryBuilder('a');

        if ($idAgriculteur !== null) {
            $qb->andWhere('a.idAgriculteur = :agriculteur')->setParameter('agriculteur', $idAgriculteur);
        }

        if ($codeAnimal !== '') {
            $qb->andWhere('a.codeAnimal LIKE :code')->setParameter('code', '%'.$codeAnimal.'%');
        }
        if ($espece !== '') {
            $qb->andWhere('a.espece LIKE :espece')->setParameter('espece', '%'.$espece.'%');
        }
        if ($race !== '') {
            $qb->andWhere('a.race LIKE :race')->setParameter('race', '%'.$race.'%');
        }
        if ($sexe !== '') {
            $qb->andWhere('a.sexe = :sexe')->setParameter('sexe', $sexe);
        }

        return $qb->orderBy('a.'.$sortBy, $order)->getQuery()->getResult();
    }
}
