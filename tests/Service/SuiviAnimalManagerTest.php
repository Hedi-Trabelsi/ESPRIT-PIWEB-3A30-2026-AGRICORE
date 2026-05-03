<?php

namespace App\Tests\Service;

use App\Entity\Animal;
use App\Entity\SuiviAnimal;
use App\Service\SuiviAnimalManager;
use PHPUnit\Framework\TestCase;

class SuiviAnimalManagerTest extends TestCase
{
    private SuiviAnimalManager $manager;

    protected function setUp(): void
    {
        $this->manager = new SuiviAnimalManager();
    }

    // ── Test suivi valide ──────────────────────────────────────────

    public function testValidSuivi(): void
    {
        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setTemperature(38.5)
              ->setPoids(450.0)
              ->setRythmeCardiaque(72)
              ->setEtatSante('Bon');

        $this->assertTrue($this->manager->validate($suivi));
    }

    // ── Règle 1 : Température entre 30 et 45 ──────────────────────

    public function testTemperatureTropBasse(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La température doit être entre 30°C et 45°C.');

        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setTemperature(25.0);

        $this->manager->validate($suivi);
    }

    public function testTemperatureTropHaute(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La température doit être entre 30°C et 45°C.');

        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setTemperature(50.0);

        $this->manager->validate($suivi);
    }

    public function testTemperatureValide(): void
    {
        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setTemperature(39.0);

        $this->assertTrue($this->manager->validate($suivi));
    }

    // ── Règle 2 : Poids positif ────────────────────────────────────

    public function testPoidsNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le poids doit être supérieur à zéro.');

        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setPoids(-10.0);

        $this->manager->validate($suivi);
    }

    public function testPoidsZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setPoids(0.0);

        $this->manager->validate($suivi);
    }

    // ── Règle 3 : Rythme cardiaque positif ────────────────────────

    public function testRythmeCardiaqueNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le rythme cardiaque doit être supérieur à zéro.');

        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setRythmeCardiaque(-5);

        $this->manager->validate($suivi);
    }

    // ── Règle 4 : État de santé valide ────────────────────────────

    public function testEtatSanteInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'état de santé doit être 'Bon', 'Moyen' ou 'Mauvais'.");

        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setEtatSante('Critique');

        $this->manager->validate($suivi);
    }

    public function testEtatSanteBonValide(): void
    {
        $suivi = new SuiviAnimal();
        $suivi->setDateSuivi(new \DateTime())
              ->setEtatSante('Bon');

        $this->assertTrue($this->manager->validate($suivi));
    }

}
