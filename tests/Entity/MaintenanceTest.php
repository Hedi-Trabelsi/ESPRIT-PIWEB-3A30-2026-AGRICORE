<?php

namespace App\Tests\Entity;

use App\Entity\Maintenance;
use PHPUnit\Framework\TestCase;

class MaintenanceTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $maintenance = new Maintenance();

        $maintenance->setNomMaintenance('Vidange');
        $maintenance->setDescription('Remplacement huile moteur');

        $this->assertSame('Vidange', $maintenance->getNomMaintenance());
        $this->assertSame('Remplacement huile moteur', $maintenance->getDescription());
    }

    public function testIsReadDefaultAndSetter(): void
    {
        $maintenance = new Maintenance();
        $this->assertFalse($maintenance->isRead());

        $maintenance->setIsRead(true);
        $this->assertTrue($maintenance->isRead());
    }
}
