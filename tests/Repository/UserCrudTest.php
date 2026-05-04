<?php

namespace App\Tests\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * CRUD tests for the User entity:
 *   testCreate  -> CREATE: insert a new row in the DB
 *   testRead    -> READ:   retrieve the row by id
 *   testUpdate  -> UPDATE: modify a column and re-read it
 *   testDelete  -> DELETE: remove the row and confirm it's gone
 *
 * Each test cleans up after itself so they can be run in any order
 * and don't pollute the real database.
 */
class UserCrudTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    private function buildUser(string $emailSuffix): User
    {
        $u = new User();
        $u->setNom('TestNom');
        $u->setPrenom('TestPrenom');
        $u->setDate(new \DateTime('1995-06-15'));
        $u->setEmail('crud_test_' . $emailSuffix . '@example.com');
        $u->setPassword(password_hash('Password1', PASSWORD_BCRYPT));
        $u->setNumeroT(21987654);
        $u->setRole(1);
        $u->setAdresse('Ariana, Tunisie');
        $u->setGenre('Homme');
        $u->setImage('');               // image column is NOT NULL in this DB
        $u->setProfile_complete(false); // profile_complete is NOT NULL in this DB
        $u->setBanned(false);           // banned is NOT NULL in this DB
        return $u;
    }

    /**
     * CREATE: persist a new user, then verify the DB assigned an id.
     */
    public function testCreate(): void
    {
        $user = $this->buildUser('create_' . uniqid());

        $this->em->persist($user);
        $this->em->flush();

        $this->assertNotNull($user->getId(), 'User id should be set after flush');
        $this->assertGreaterThan(0, $user->getId(), 'User id should be a positive integer');

        // cleanup
        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * READ: persist a user, then look it up by id and verify the fields.
     */
    public function testRead(): void
    {
        $user = $this->buildUser('read_' . uniqid());
        $this->em->persist($user);
        $this->em->flush();
        $id = $user->getId();
        $this->em->clear(); // detach so the next find() really hits the DB

        $found = $this->em->getRepository(User::class)->find($id);

        $this->assertNotNull($found, 'User should be retrievable by id');
        $this->assertSame($id, $found->getId());
        $this->assertSame('TestNom', $found->getNom());
        $this->assertSame('TestPrenom', $found->getPrenom());
        $this->assertSame('Ariana, Tunisie', $found->getAdresse());
        $this->assertSame(1, $found->getRole());

        // cleanup
        $this->em->remove($found);
        $this->em->flush();
    }

    /**
     * UPDATE: persist a user, change a field, flush, and verify the change is in DB.
     */
    public function testUpdate(): void
    {
        $user = $this->buildUser('update_' . uniqid());
        $this->em->persist($user);
        $this->em->flush();
        $id = $user->getId();

        $user->setAdresse('Sfax, Tunisie');
        $user->setNumeroT(22111222);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(User::class)->find($id);
        $this->assertNotNull($found);
        $this->assertSame('Sfax, Tunisie', $found->getAdresse(), 'Adresse should be updated');
        $this->assertSame(22111222, $found->getNumeroT(), 'NumeroT should be updated');

        // cleanup
        $this->em->remove($found);
        $this->em->flush();
    }

    /**
     * DELETE: persist a user, remove it, then verify it's gone.
     */
    public function testDelete(): void
    {
        $user = $this->buildUser('delete_' . uniqid());
        $this->em->persist($user);
        $this->em->flush();
        $id = $user->getId();

        $this->em->remove($user);
        $this->em->flush();
        $this->em->clear();

        $found = $this->em->getRepository(User::class)->find($id);
        $this->assertNull($found, 'User should no longer be in the database');
    }

    protected function tearDown(): void
    {
        // Safety net: remove any leftover crud_test_ rows in case a test failed midway
        $leftovers = $this->em->getRepository(User::class)->createQueryBuilder('u')
            ->where('u.email LIKE :p')
            ->setParameter('p', 'crud_test_%@example.com')
            ->getQuery()->getResult();
        foreach ($leftovers as $u) {
            $this->em->remove($u);
        }
        if ($leftovers) {
            $this->em->flush();
        }

        parent::tearDown();
    }
}
