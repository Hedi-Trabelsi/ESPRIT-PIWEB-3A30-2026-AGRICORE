<?php

namespace App\Tests\Service;

use App\Entity\Tache;
use App\Service\TacheManager;
use PHPUnit\Framework\TestCase;

class TacheManagerTest extends TestCase
{
    public function testValidTache()
    {
        $tache = new Tache();
        
        $tache->setNomTache('Changer filtre');
        $tache->setDatePrevue(new \DateTimeImmutable('+1 day'));

        $manager = new TacheManager();
        $this->assertTrue($manager->validate($tache));
    }

    public function testTacheWithoutName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $tache = new Tache();
        $tache->setDatePrevue(new \DateTimeImmutable('+1 day'));

        $manager = new TacheManager();
        $manager->validate($tache);
    }

    public function testTacheWithPastDate()
    {
        $this->expectException(\InvalidArgumentException::class);
        $tache = new Tache();
        $tache->setNomTache('Tache ancienne');
        $tache->setDatePrevue(new \DateTimeImmutable('-1 day'));

        $manager = new TacheManager();
        $manager->validate($tache);
    }
}
