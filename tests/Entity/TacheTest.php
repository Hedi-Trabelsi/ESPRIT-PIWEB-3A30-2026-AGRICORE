<?php

namespace App\Tests\Entity;

use App\Entity\Tache;
use App\Entity\Maintenance;
use PHPUnit\Framework\TestCase;

class TacheTest extends TestCase
{
    public function testSettersAndGetters()
    {
        $tache = new Tache();

        $tache->setNomTache('Changer filtre')
            ->setCoutEstimee('12.00')
            ->setDatePrevue(new \DateTimeImmutable('+1 day'))
            ->setDescription('Description tache');

        $this->assertSame('Changer filtre', $tache->getNomTache());
        $this->assertSame('12.00', $tache->getCoutEstimee());
        $this->assertInstanceOf(\DateTimeInterface::class, $tache->getDatePrevue());
        $this->assertSame('Description tache', $tache->getDescription());

        $maintenance = new Maintenance();
        $maintenance->setNomMaintenance('M1')->setDescription('desc');
        $tache->setIdMaintenance($maintenance);
        $this->assertSame($maintenance, $tache->getIdMaintenance());

        $tache->setEvaluation(1);
        $this->assertSame(1, $tache->getEvaluation());

        $tache->setEtat(0);
        $this->assertSame(0, $tache->getEtat());
    }
}
