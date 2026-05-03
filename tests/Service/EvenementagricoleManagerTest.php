<?php

namespace App\Tests\Service;

use App\Entity\Evennementagricole;
use App\Service\EvenementagricoleManager;
use PHPUnit\Framework\TestCase;

class EvenementagricoleManagerTest extends TestCase
{
    public function testValidEvenement(): void
    {
        $event = new Evennementagricole();
        $event->setTitre('Valid Event Title');
        $event->setDescription('This is a valid description that is definitely long enough.');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+1 day'));
        $event->setDateFin(new \DateTime('+2 days'));
        $event->setFraisInscription(50);
        $event->setCapaciteMax(100);

        $manager = new EvenementagricoleManager();
        $this->assertTrue($manager->validate($event));
    }

    public function testEvenementWithoutTitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $event = new Evennementagricole();
        $event->setDescription('Valid description here');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+1 day'));
        $event->setDateFin(new \DateTime('+2 days'));
        $event->setFraisInscription(50);
        $event->setCapaciteMax(100);

        $manager = new EvenementagricoleManager();
        $manager->validate($event);
    }

    public function testEvenementShortTitre(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $event = new Evennementagricole();
        $event->setTitre('Hi');
        $event->setDescription('Valid description here');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+1 day'));
        $event->setDateFin(new \DateTime('+2 days'));
        $event->setFraisInscription(50);
        $event->setCapaciteMax(100);

        $manager = new EvenementagricoleManager();
        $manager->validate($event);
    }

    public function testEvenementShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $event = new Evennementagricole();
        $event->setTitre('Valid Title');
        $event->setDescription('Too short');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+1 day'));
        $event->setDateFin(new \DateTime('+2 days'));
        $event->setFraisInscription(50);
        $event->setCapaciteMax(100);

        $manager = new EvenementagricoleManager();
        $manager->validate($event);
    }

    public function testEvenementInvalidDateOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $event = new Evennementagricole();
        $event->setTitre('Valid Title');
        $event->setDescription('Valid description');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+2 days'));
        $event->setDateFin(new \DateTime('+1 day'));
        $event->setFraisInscription(50);
        $event->setCapaciteMax(100);

        $manager = new EvenementagricoleManager();
        $manager->validate($event);
    }

    public function testEvenementNegativeFrais(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $event = new Evennementagricole();
        $event->setTitre('Valid Title');
        $event->setDescription('Valid description');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+1 day'));
        $event->setDateFin(new \DateTime('+2 days'));
        $event->setFraisInscription(-10);
        $event->setCapaciteMax(100);

        $manager = new EvenementagricoleManager();
        $manager->validate($event);
    }

    public function testEvenementZeroCapacite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $event = new Evennementagricole();
        $event->setTitre('Valid Title');
        $event->setDescription('Valid description');
        $event->setLieu('Valid Location');
        $event->setDateDebut(new \DateTime('+1 day'));
        $event->setDateFin(new \DateTime('+2 days'));
        $event->setFraisInscription(50);
        $event->setCapaciteMax(0);

        $manager = new EvenementagricoleManager();
        $manager->validate($event);
    }

    public function testGetDiscountPercentage(): void
    {
        $manager = new EvenementagricoleManager();
        $this->assertEquals(0, $manager->getDiscountPercentage(0));
        $this->assertEquals(10, $manager->getDiscountPercentage(10));
        $this->assertEquals(50, $manager->getDiscountPercentage(55));
        $this->assertEquals(100, $manager->getDiscountPercentage(100));
        $this->assertEquals(100, $manager->getDiscountPercentage(150));
    }
}
