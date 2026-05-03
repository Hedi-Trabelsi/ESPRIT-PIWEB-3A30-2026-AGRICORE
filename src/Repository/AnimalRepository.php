<?php

namespace App\Repository;

use App\Entity\Animal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Animal>
 */
class AnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Animal::class);
    }

    /**
     * Retourne une Query (pour pagination) au lieu d'un tableau
     * Résout les warnings DoctrineDoctor : findAll sans LIMIT + ORDER BY sans LIMIT
     */
    public function searchQuery(string $q = '', string $sortBy = 'codeAnimal', string $order = 'ASC', ?int $idAgriculteur = null): \Doctrine\ORM\Query
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

        return $qb->orderBy('a.'.$sortBy, $order)->getQuery();
    }

    /** Compte total des animaux — remplace findAll() + count() */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.idAnimal)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Compte par espèce — remplace findAll() + foreach
     * @return array<string, int>
     */
    public function countByEspece(): array
    {
        /** @var array<array{espece: string, nb: string}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('a.espece, COUNT(a.idAnimal) AS nb')
            ->groupBy('a.espece')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['espece']] = (int) $row['nb'];
        }
        return $result;
    }

    /** Compte par race — remplace findAll() + foreach
     * @return array<string, int>
     */
    public function countByRace(): array
    {
        /** @var array<array{race: string, nb: string}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('a.race, COUNT(a.idAnimal) AS nb')
            ->groupBy('a.race')
            ->orderBy('nb', 'DESC')
            ->getQuery()
            ->getResult();

        $result = [];
        foreach ($rows as $row) {
            $result[(string) $row['race']] = (int) $row['nb'];
        }
        return $result;
    }

    /** Compte par sexe — remplace findAll() + foreach
     * @return array<string, int>
     */
    public function countBySexe(): array
    {
        /** @var array<array{sexe: string, nb: string}> $rows */
        $rows = $this->createQueryBuilder('a')
            ->select('a.sexe, COUNT(a.idAnimal) AS nb')
            ->groupBy('a.sexe')
            ->getQuery()
            ->getResult();

        $result = ['Mâle' => 0, 'Femelle' => 0];
        foreach ($rows as $row) {
            $result[(string) $row['sexe']] = (int) $row['nb'];
        }
        return $result;
    }

    /**
     * @return Animal[]
     */
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

        $result = $qb->orderBy('a.'.$sortBy, $order)->getQuery()->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof Animal)) : [];
    }

    /**
     * @return Animal[]
     */
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

        $result = $qb->orderBy('a.'.$sortBy, $order)->getQuery()->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof Animal)) : [];
    }
}