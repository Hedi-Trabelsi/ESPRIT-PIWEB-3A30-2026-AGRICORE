<?php

namespace App\Repository;

use App\Entity\SuiviAnimal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SuiviAnimal>
 */
class SuiviAnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SuiviAnimal::class);
    }

    /**
     * Charge les suivis d'un animal avec LIMIT — évite ORDER BY sans LIMIT
     * @return SuiviAnimal[]
     */
    public function findByAnimalLimited(\App\Entity\Animal $animal, int $limit = 20): array
    {
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.animal = :animal')
            ->setParameter('animal', $animal)
            ->orderBy('s.dateSuivi', 'DESC')
            ->setMaxResults($limit)           // ← LIMIT explicite
            ->getQuery()
            ->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof SuiviAnimal)) : [];
    }

    /**
     * @return SuiviAnimal[]
     */
    public function findByAnimalAndPeriode(\App\Entity\Animal $animal, string $dateDebut, string $dateFin): array
    {
        $result = $this->createQueryBuilder('s')
            ->andWhere('s.animal = :animal')
            ->andWhere('s.dateSuivi >= :debut')
            ->andWhere('s.dateSuivi <= :fin')
            ->setParameter('animal', $animal)
            ->setParameter('debut', new \DateTime($dateDebut.' 00:00:00'))
            ->setParameter('fin',   new \DateTime($dateFin.' 23:59:59'))
            ->orderBy('s.dateSuivi', 'ASC')
            ->getQuery()
            ->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof SuiviAnimal)) : [];
    }

    /**
     * Retourne une Query (pour pagination) au lieu d'un tableau
     * Résout les warnings DoctrineDoctor : findAll sans LIMIT + ORDER BY sans LIMIT
     */
    public function searchQuery(string $q = '', string $sortBy = 'dateSuivi', string $order = 'DESC', ?int $idAgriculteur = null): \Doctrine\ORM\Query
    {
        $allowed = ['dateSuivi', 'temperature', 'poids', 'rythmeCardiaque', 'etatSante', 'niveauActivite'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'dateSuivi';
        $order   = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $this->createQueryBuilder('s')
            ->addSelect('a')               // JOIN FETCH — charge animal en même temps
            ->join('s.animal', 'a');       // INNER JOIN explicite, pas de lazy loading

        if ($idAgriculteur !== null) {
            $qb->andWhere('a.idAgriculteur = :agriculteur')
               ->setParameter('agriculteur', $idAgriculteur);
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

        // HINT_FORCE_PARTIAL_LOAD évite que Doctrine fasse une 2e requête
        // pour résoudre les proxies d'entités liées (le IN(?,?,?) problématique)
        return $qb->orderBy('s.'.$sortBy, $order)
                  ->getQuery()
                  ->setHint(\Doctrine\ORM\Query::HINT_FORCE_PARTIAL_LOAD, true);
    }

    /** Compte total des suivis — remplace findAll() + count() */
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.idSuivi)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Compte par état de santé — remplace findAll() + foreach
     * @return array<string, int>
     */
    public function countByEtatSante(): array
    {
        /** @var array<array{etatSante: string, nb: string}> $rows */
        $rows = $this->createQueryBuilder('s')
            ->select('s.etatSante, COUNT(s.idSuivi) AS nb')
            ->groupBy('s.etatSante')
            ->getQuery()
            ->getResult();

        $result = ['Bon' => 0, 'Moyen' => 0, 'Mauvais' => 0];
        foreach ($rows as $row) {
            if (isset($result[$row['etatSante']])) {
                $result[(string) $row['etatSante']] = (int) $row['nb'];
            }
        }
        return $result;
    }

    /** Compte par niveau d'activité — remplace findAll() + foreach
     * @return array<string, int>
     */
    public function countByNiveauActivite(): array
    {
        /** @var array<array{niveauActivite: string, nb: string}> $rows */
        $rows = $this->createQueryBuilder('s')
            ->select('s.niveauActivite, COUNT(s.idSuivi) AS nb')
            ->groupBy('s.niveauActivite')
            ->getQuery()
            ->getResult();

        $result = ['Faible' => 0, 'Modéré' => 0, 'Élevé' => 0];
        foreach ($rows as $row) {
            if (isset($result[$row['niveauActivite']])) {
                $result[(string) $row['niveauActivite']] = (int) $row['nb'];
            }
        }
        return $result;
    }

    /** Moyennes température, poids, rythme — remplace findAll() + foreach
     * @return array{moyTemp: float, moyPoids: float, moyRythme: float}
     */
    public function getMoyennes(): array
    {
        /** @var array{moyTemp: string|null, moyPoids: string|null, moyRythme: string|null}|null $row */
        $row = $this->createQueryBuilder('s')
            ->select(
                'ROUND(AVG(s.temperature), 1) AS moyTemp',
                'ROUND(AVG(s.poids), 1)        AS moyPoids',
                'ROUND(AVG(s.rythmeCardiaque), 0) AS moyRythme'
            )
            ->getQuery()
            ->getOneOrNullResult();

        return [
            'moyTemp'   => (float) ($row['moyTemp']   ?? 0),
            'moyPoids'  => (float) ($row['moyPoids']  ?? 0),
            'moyRythme' => (float) ($row['moyRythme'] ?? 0),
        ];
    }

    /** Compte des suivis par mois (N derniers mois) — remplace findAll() + foreach
     * @return array<string, int>
     */
    public function countByMois(int $limit = 6): array
    {
        /** @var array<array{mois: string, nb: string}> $rows */
        $rows = $this->createQueryBuilder('s')
            ->select("SUBSTRING(s.dateSuivi, 1, 7) AS mois, COUNT(s.idSuivi) AS nb")
            ->groupBy('mois')
            ->orderBy('mois', 'ASC')
            ->getQuery()
            ->getResult();

        $parMois = [];
        foreach ($rows as $row) {
            $parMois[(string) $row['mois']] = (int) $row['nb'];
        }

        return array_slice($parMois, -$limit, $limit, true);
    }

    /**
     * @return SuiviAnimal[]
     */
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

        $result = $qb->orderBy('s.'.$sortBy, $order)->getQuery()->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof SuiviAnimal)) : [];
    }

    /**
     * @return SuiviAnimal[]
     */
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

        $result = $qb->orderBy('s.'.$sortBy, $order)->getQuery()->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof SuiviAnimal)) : [];
    }
}
