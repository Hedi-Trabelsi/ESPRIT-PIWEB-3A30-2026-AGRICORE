<?php

namespace App\Tests\Controller;

use App\Controller\MaintenanceController;
use App\Entity\Maintenance;
use App\Entity\Tache;
use PHPUnit\Framework\TestCase;

class MaintenanceControllerTest extends TestCase
{
    public function testExtractUrgentOverdueMaintenances()
    {
        $m1 = new Maintenance();
        $m1->setStatut('En attente');
        $m1->setPriorite('Urgente');
        $m1->setDateDeclaration(new \DateTimeImmutable('2000-01-01'));

        $m2 = new Maintenance();
        $m2->setStatut('En attente');
        $m2->setPriorite('Normale');
        $m2->setDateDeclaration(new \DateTimeImmutable('2000-01-01'));

        $m3 = new Maintenance();
        $m3->setStatut('Résolue');
        $m3->setPriorite('Urgente');
        $m3->setDateDeclaration(new \DateTimeImmutable('2000-01-01'));

        $controller = new MaintenanceController();

        $ref = new \ReflectionClass(MaintenanceController::class);
        $method = $ref->getMethod('extractUrgentOverdueMaintenances');
        $method->setAccessible(true);

        $result = $method->invoke($controller, [$m1, $m2, $m3]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('Urgente', $result[0]->getPriorite());
        $this->assertSame('En attente', $result[0]->getStatut());
    }

    public function testBuildCalendarTaskData()
    {
        $maintenance = new Maintenance();
        $maintenance->setNomMaintenance('M1');
        $maintenance->setLieu('Ferme');
        $maintenance->setStatut('En attente');

        // initialize typed id_maintenance to avoid uninitialized property access
        $mProp = new \ReflectionProperty(Maintenance::class, 'id_maintenance');
        $mProp->setAccessible(true);
        $mProp->setValue($maintenance, 11);

        $tache = new Tache();
        $tache->setNomTache('T1')
            ->setDatePrevue(new \DateTimeImmutable('-2 days'))
            ->setEtat(0)
            ->setDescription('desc')
            ->setCoutEstimee('100')
            ->setIdMaintenance($maintenance);

        // Ensure typed id_tache is initialized to avoid typed property access error
        $prop = new \ReflectionProperty(Tache::class, 'id_tache');
        $prop->setAccessible(true);
        $prop->setValue($tache, 123);

        $controller = new MaintenanceController();
        $ref = new \ReflectionClass(MaintenanceController::class);
        $method = $ref->getMethod('buildCalendarTaskData');
        $method->setAccessible(true);

        $data = $method->invoke($controller, $tache, (new \DateTimeImmutable('today'))->format('Y-m-d'));

        $this->assertIsArray($data);
        $this->assertArrayHasKey('isOverdue', $data);
        $this->assertArrayHasKey('maintenanceName', $data);
        $this->assertSame('M1', $data['maintenanceName']);
    }
}
