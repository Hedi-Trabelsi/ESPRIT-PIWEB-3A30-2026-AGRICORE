<?php

namespace App\Tests\Controller;

use App\Controller\EvenementController;
use App\Entity\Evennementagricole;
use App\Entity\Participants;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EvenementControllerTest extends TestCase
{
    public function testGetDiscountPercentage(): void
    {
        $controller = new EvenementController();
        $ref = new \ReflectionClass(EvenementController::class);
        $method = $ref->getMethod('getDiscountPercentage');
        $method->setAccessible(true);

        $this->assertEquals(0, $method->invoke($controller, 0));
        $this->assertEquals(10, $method->invoke($controller, 10));
        $this->assertEquals(50, $method->invoke($controller, 55));
        $this->assertEquals(100, $method->invoke($controller, 100));
        $this->assertEquals(100, $method->invoke($controller, 150));
    }

    public function testGetStatutLogic(): void
    {
        $controller = new EvenementController();

        $pastEvent = new Evennementagricole();
        $pastEvent->setDateFin((new \DateTime())->modify('-2 days'));

        $this->assertEquals('HISTORIQUE', $pastEvent->getStatut());

        $comingEvent = new Evennementagricole();
        $comingEvent->setDateDebut((new \DateTime())->modify('+2 days'));

        $this->assertEquals('COMING', $comingEvent->getStatut());

        $ongoingEvent = new Evennementagricole();
        $ongoingEvent->setDateDebut((new \DateTime())->modify('-2 days'));
        $ongoingEvent->setDateFin((new \DateTime())->modify('+2 days'));

        $this->assertEquals('EN_COURS', $ongoingEvent->getStatut());
    }
}
