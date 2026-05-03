<?php

namespace App\Tests\Service;

use App\Entity\Maintenance;
use App\Service\MaintenanceManager;
use PHPUnit\Framework\TestCase;

class MaintenanceManagerTest extends TestCase
{
    public function testValidMaintenance(): void
    {
        $maintenance = new Maintenance();
        $maintenance->setNomMaintenance('Vidange Moteur');
        $maintenance->setDescription('Remplacer l\'huile moteur et le filtre');

        $manager = new MaintenanceManager();
        $this->assertTrue($manager->validate($maintenance));
    }

    public function testMaintenanceWithoutName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $maintenance = new Maintenance();
        $maintenance->setDescription('Description correcte ici');

        $manager = new MaintenanceManager();
        $manager->validate($maintenance);
    }

    public function testMaintenanceWithShortDescription(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $maintenance = new Maintenance();
        $maintenance->setNomMaintenance('Test');
        $maintenance->setDescription('court');

        $manager = new MaintenanceManager();
        $manager->validate($maintenance);
    }
}
