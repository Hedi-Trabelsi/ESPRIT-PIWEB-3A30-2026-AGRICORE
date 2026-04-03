<?php

namespace App\Repository\utilisateur;

use App\Entity\utilisateur\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?Utilisateur
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find users by role
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count users by role
     */
    public function countByRole(string $role): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"' . $role . '"%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Search users by name or email
     */
    public function searchUsers(string $searchTerm): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.nom LIKE :search')
            ->orWhere('u.prenom LIKE :search')
            ->orWhere('u.email LIKE :search')
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('u.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get all admins
     */
    public function findAdmins(): array
    {
        return $this->findByRole('ROLE_ADMIN');
    }

    /**
     * Get all farmers
     */
    public function findFarmers(): array
    {
        return $this->findByRole('ROLE_FARMER');
    }

    /**
     * Get all technicians
     */
    public function findTechnicians(): array
    {
        return $this->findByRole('ROLE_TECHNICIAN');
    }

    /**
     * Get all suppliers
     */
    public function findSuppliers(): array
    {
        return $this->findByRole('ROLE_SUPPLIER');
    }

    /**
     * Get users with incomplete profile
     */
    public function findIncompleteProfiles(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.adresse IS NULL OR u.adresse = :empty')
            ->orWhere('u.telephone IS NULL OR u.telephone = :empty')
            ->setParameter('empty', '')
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
