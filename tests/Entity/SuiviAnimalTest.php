<?php

namespace App\Tests\Entity;

use App\Entity\Animal;
use App\Entity\SuiviAnimal;
use PHPUnit\Framework\TestCase;

class SuiviAnimalTest extends TestCase
{
    private SuiviAnimal $suivi;

    protected function setUp(): void
    {
        $this->suivi = new SuiviAnimal();
    }

    // ── Tests getters/setters ──────────────────────────────────────

    public function testSetAndGetAnimal(): void
    {
        $animal = new Animal();
        $animal->setCodeAnimal('A001');
        $this->suivi->setAnimal($animal);
        $this->assertSame($animal, $this->suivi->getAnimal());
    }

    public function testSetAndGetDateSuivi(): void
    {
        $date = new \DateTime('2026-04-01 10:00:00');
        $this->suivi->setDateSuivi($date);
        $this->assertSame($date, $this->suivi->getDateSuivi());
    }

    public function testSetAndGetTemperature(): void
    {
        $this->suivi->setTemperature(38.5);
        $this->assertSame(38.5, $this->suivi->getTemperature());
    }

    public function testSetAndGetPoids(): void
    {
        $this->suivi->setPoids(450.0);
        $this->assertSame(450.0, $this->suivi->getPoids());
    }

    public function testSetAndGetRythmeCardiaque(): void
    {
        $this->suivi->setRythmeCardiaque(72);
        $this->assertSame(72, $this->suivi->getRythmeCardiaque());
    }

    public function testSetAndGetNiveauActivite(): void
    {
        $this->suivi->setNiveauActivite('Modéré');
        $this->assertSame('Modéré', $this->suivi->getNiveauActivite());
    }

    public function testSetAndGetEtatSante(): void
    {
        $this->suivi->setEtatSante('Bon');
        $this->assertSame('Bon', $this->suivi->getEtatSante());
    }

    public function testSetAndGetRemarque(): void
    {
        $this->suivi->setRemarque('Animal en bonne santé.');
        $this->assertSame('Animal en bonne santé.', $this->suivi->getRemarque());
    }

    // ── Tests valeurs initiales ────────────────────────────────────

    public function testIdSuiviIsNullByDefault(): void
    {
        $this->assertNull($this->suivi->getIdSuivi());
    }

    public function testAnimalIsNullByDefault(): void
    {
        $this->assertNull($this->suivi->getAnimal());
    }

    public function testTemperatureIsNullByDefault(): void
    {
        $this->assertNull($this->suivi->getTemperature());
    }

    public function testPoidsIsNullByDefault(): void
    {
        $this->assertNull($this->suivi->getPoids());
    }

    public function testRythmeCardiaqueIsNullByDefault(): void
    {
        $this->assertNull($this->suivi->getRythmeCardiaque());
    }

    // ── Tests logique métier ───────────────────────────────────────

    public function testEtatSanteBon(): void
    {
        $this->suivi->setEtatSante('Bon');
        $this->assertSame('Bon', $this->suivi->getEtatSante());
    }

    public function testEtatSanteMoyen(): void
    {
        $this->suivi->setEtatSante('Moyen');
        $this->assertSame('Moyen', $this->suivi->getEtatSante());
    }

    public function testEtatSanteMauvais(): void
    {
        $this->suivi->setEtatSante('Mauvais');
        $this->assertSame('Mauvais', $this->suivi->getEtatSante());
    }

    public function testNiveauActiviteFaible(): void
    {
        $this->suivi->setNiveauActivite('Faible');
        $this->assertSame('Faible', $this->suivi->getNiveauActivite());
    }

    public function testNiveauActiviteEleve(): void
    {
        $this->suivi->setNiveauActivite('Élevé');
        $this->assertSame('Élevé', $this->suivi->getNiveauActivite());
    }

    public function testTemperatureIsFloat(): void
    {
        $this->suivi->setTemperature(39.2);
        $this->assertIsFloat($this->suivi->getTemperature());
    }

    public function testPoidsIsFloat(): void
    {
        $this->suivi->setPoids(320.5);
        $this->assertIsFloat($this->suivi->getPoids());
    }

    public function testRythmeCardiaqueIsInt(): void
    {
        $this->suivi->setRythmeCardiaque(80);
        $this->assertIsInt($this->suivi->getRythmeCardiaque());
    }

    public function testDateSuiviIsDateTimeInterface(): void
    {
        $date = new \DateTime();
        $this->suivi->setDateSuivi($date);
        $this->assertInstanceOf(\DateTimeInterface::class, $this->suivi->getDateSuivi());
    }

    public function testSetterReturnsSelf(): void
    {
        $result = $this->suivi->setEtatSante('Bon');
        $this->assertInstanceOf(SuiviAnimal::class, $result);
    }

    public function testRemarqueCanBeNull(): void
    {
        $this->suivi->setRemarque(null);
        $this->assertNull($this->suivi->getRemarque());
    }

    public function testAnimalRelation(): void
    {
        $animal = new Animal();
        $animal->setCodeAnimal('Z999')->setEspece('Caprin');
        $this->suivi->setAnimal($animal);

        $linked = $this->suivi->getAnimal();
        $this->assertNotNull($linked);
        $this->assertSame('Z999', $linked->getCodeAnimal());
        $this->assertSame('Caprin', $linked->getEspece());
    }

    public function testMultipleFieldsSetCorrectly(): void
    {
        $date = new \DateTime('2026-03-15');
        $animal = new Animal();
        $animal->setCodeAnimal('A005');

        $this->suivi
            ->setAnimal($animal)
            ->setDateSuivi($date)
            ->setTemperature(38.8)
            ->setPoids(500.0)
            ->setRythmeCardiaque(65)
            ->setNiveauActivite('Modéré')
            ->setEtatSante('Bon')
            ->setRemarque('RAS');

        $linked2 = $this->suivi->getAnimal();
        $this->assertNotNull($linked2);
        $this->assertSame('A005', $linked2->getCodeAnimal());
        $this->assertSame($date, $this->suivi->getDateSuivi());
        $this->assertSame(38.8, $this->suivi->getTemperature());
        $this->assertSame(500.0, $this->suivi->getPoids());
        $this->assertSame(65, $this->suivi->getRythmeCardiaque());
        $this->assertSame('Modéré', $this->suivi->getNiveauActivite());
        $this->assertSame('Bon', $this->suivi->getEtatSante());
        $this->assertSame('RAS', $this->suivi->getRemarque());
    }
}
