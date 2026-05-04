<?php

namespace App\Tests\Entity;

use App\Entity\User;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testEntityInitialization(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
        $this->assertEquals('', $user->getNom());
        $this->assertEquals('', $user->getPrenom());
        $this->assertNull($user->getDate());
        $this->assertEquals('', $user->getAdresse());
        $this->assertEquals(0, $user->getRole());
        $this->assertEquals(0, $user->getNumeroT());
        $this->assertEquals('', $user->getEmail());
        $this->assertNull($user->getImage());
        $this->assertEquals('', $user->getPassword());
        $this->assertEquals('', $user->getGenre());
        $this->assertNull($user->isProfile_complete());
        $this->assertFalse($user->isBanned());
    }

    public function testCollectionsInitialization(): void
    {
        $user = new User();
        $this->assertInstanceOf(Collection::class, $user->getDepenses());
        $this->assertCount(0, $user->getDepenses());
        $this->assertInstanceOf(Collection::class, $user->getEquipements());
        $this->assertCount(0, $user->getEquipements());
        $this->assertInstanceOf(Collection::class, $user->getMaintenances());
        $this->assertCount(0, $user->getMaintenances());
        $this->assertInstanceOf(Collection::class, $user->getVentes());
        $this->assertCount(0, $user->getVentes());
    }

    public function testSettersAndGetters(): void
    {
        $user = new User();

        $user->setNom('Trabelsi');
        $this->assertEquals('Trabelsi', $user->getNom());

        $user->setPrenom('Hedi');
        $this->assertEquals('Hedi', $user->getPrenom());

        $date = new \DateTime('1995-06-15');
        $user->setDate($date);
        $this->assertEquals($date, $user->getDate());

        $user->setAdresse('Ariana, Tunisie');
        $this->assertEquals('Ariana, Tunisie', $user->getAdresse());

        $user->setRole(1);
        $this->assertEquals(1, $user->getRole());

        $user->setNumeroT(21987654);
        $this->assertEquals(21987654, $user->getNumeroT());

        $user->setEmail('hedi.trabelsi@gmail.com');
        $this->assertEquals('hedi.trabelsi@gmail.com', $user->getEmail());

        $user->setPassword('Password1');
        $this->assertEquals('Password1', $user->getPassword());

        $user->setGenre('Homme');
        $this->assertEquals('Homme', $user->getGenre());

        $user->setProfile_complete(true);
        $this->assertTrue($user->isProfile_complete());

        $user->setBanned(true);
        $this->assertTrue($user->isBanned());
    }

    public function testImageSetterAndGetter(): void
    {
        $user = new User();
        $user->setImage('base64encodedimagedata');
        $this->assertEquals('base64encodedimagedata', $user->getImage());
    }

    public function testImageReturnsNullWhenUnset(): void
    {
        $user = new User();
        $this->assertNull($user->getImage());
    }

    public function testPrepareForSessionStripsImage(): void
    {
        $user = new User();
        $user->setImage('some_large_blob_data');
        $this->assertNotNull($user->getImage());

        $user->prepareForSession();
        $this->assertNull($user->getImage());
    }

    public function testPrepareForSessionReturnsSelf(): void
    {
        $user = new User();
        $this->assertSame($user, $user->prepareForSession());
    }

    public function testRoleAdmin(): void
    {
        $user = new User();
        $user->setRole(0);
        $this->assertEquals(0, $user->getRole());
    }

    public function testRoleAgriculteur(): void
    {
        $user = new User();
        $user->setRole(1);
        $this->assertEquals(1, $user->getRole());
    }

    public function testRoleTechnicien(): void
    {
        $user = new User();
        $user->setRole(2);
        $this->assertEquals(2, $user->getRole());
    }

    public function testBannedDefaultsToFalse(): void
    {
        $user = new User();
        $this->assertFalse($user->isBanned());
    }

    public function testFluentSetters(): void
    {
        $user = new User();
        $result = $user
            ->setNom('Trabelsi')
            ->setPrenom('Hedi')
            ->setEmail('hedi@gmail.com')
            ->setPassword('Password1')
            ->setRole(1)
            ->setNumeroT(21987654)
            ->setAdresse('Ariana')
            ->setGenre('Homme');

        $this->assertSame($user, $result);
        $this->assertEquals('Trabelsi', $user->getNom());
    }
}
