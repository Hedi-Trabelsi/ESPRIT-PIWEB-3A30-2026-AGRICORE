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
     * Fetch all users WITHOUT loading the image BLOB column (which can be 200KB-2MB per user).
     * Avatars on the back-office list are fetched separately via the app_user_avatar route.
     *
     * @return User[]
     */
    public function findAllWithoutImage(): array
    {
        $result = $this->createQueryBuilder('u')
            ->select('partial u.{id, nom, prenom, date, adresse, role, numeroT, email, password, genre, profile_complete, banned}')
            ->getQuery()
            ->getResult();
        return is_array($result) ? array_values(array_filter($result, fn($r) => $r instanceof User)) : [];
    }
}
