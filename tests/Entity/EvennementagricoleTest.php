<?php

namespace App\Tests\Entity;

use App\Entity\Evennementagricole;
use PHPUnit\Framework\TestCase;

class EvennementagricoleTest extends TestCase
{
    public function testEntityInitialization(): void
    {
        $event = new Evennementagricole();
        $this->assertNull($event->getIdEv());
        $this->assertNull($event->getTitre());
        $this->assertNull($event->getDescription());
        $this->assertNull($event->getDateDebut());
        $this->assertNull($event->getDateFin());
        $this->assertNull($event->getLieu());
        $this->assertNull($event->getCapaciteMax());
        $this->assertNull($event->getFraisInscription());
        $this->assertNull($event->getImage());
    }

    public function testSettersAndGetters(): void
    {
        $event = new Evennementagricole();
        
        $event->setTitre('Test Event');
        $this->assertEquals('Test Event', $event->getTitre());
        
        $event->setDescription('Test Description');
        $this->assertEquals('Test Description', $event->getDescription());
        
        $dateDebut = new \DateTime('2026-06-01 10:00:00');
        $event->setDateDebut($dateDebut);
        $this->assertEquals($dateDebut, $event->getDateDebut());
        
        $dateFin = new \DateTime('2026-06-02 18:00:00');
        $event->setDateFin($dateFin);
        $this->assertEquals($dateFin, $event->getDateFin());
        
        $event->setLieu('Test Location');
        $this->assertEquals('Test Location', $event->getLieu());
        
        $event->setCapaciteMax(100);
        $this->assertEquals(100, $event->getCapaciteMax());
        
        $event->setFraisInscription(50);
        $this->assertEquals(50, $event->getFraisInscription());
        
        $event->setImage('test_image_data');
        $this->assertEquals('test_image_data', $event->getImage());
    }

    public function testGetStatutHistorique(): void
    {
        $event = new Evennementagricole();
        $pastDate = (new \DateTime())->modify('-2 days');
        $event->setDateFin($pastDate);
        
        $this->assertEquals('HISTORIQUE', $event->getStatut());
    }

    public function testGetStatutComing(): void
    {
        $event = new Evennementagricole();
        $futureDate = (new \DateTime())->modify('+2 days');
        $event->setDateDebut($futureDate);
        
        $this->assertEquals('COMING', $event->getStatut());
    }

    public function testGetStatutEnCours(): void
    {
        $event = new Evennementagricole();
        $pastDate = (new \DateTime())->modify('-2 days');
        $futureDate = (new \DateTime())->modify('+2 days');
        $event->setDateDebut($pastDate);
        $event->setDateFin($futureDate);
        
        $this->assertEquals('EN_COURS', $event->getStatut());
    }
}
