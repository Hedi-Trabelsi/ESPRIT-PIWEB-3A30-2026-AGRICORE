<?php

namespace App\Tests\Controller;

use App\Controller\EvenementBackController;
use App\Entity\Evennementagricole;
use PHPUnit\Framework\TestCase;

class EvenementBackControllerTest extends TestCase
{
    public function testEvenementStatut()
    {
        $event = new Evennementagricole();
        $event->setTitre('Test Back Office Event');
        $event->setDescription('Test description');
        $event->setLieu('Test Location');
        $event->setDateDebut((new \DateTime())->modify('+1 day'));
        $event->setDateFin((new \DateTime())->modify('+2 days'));
        $event->setCapaciteMax(50);
        $event->setFraisInscription(30);

        $this->assertEquals('COMING', $event->getStatut());
    }
}
