<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?User
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Fetch all users for the back-office list as scalar arrays — never loads image BLOBs.
     * Avatars are served separately via the app_user_avatar route.
     *
     * @return array<int,array<string,mixed>>
     */
    public function findAllForBackList(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.id, u.nom, u.prenom, u.date, u.adresse, u.role, u.numeroT, u.email, u.genre, u.profile_complete, u.banned')
            ->orderBy('u.id', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }
}
