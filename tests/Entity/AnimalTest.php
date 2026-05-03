<?php

namespace App\Tests\Entity;

use App\Entity\Animal;
use PHPUnit\Framework\TestCase;

class AnimalTest extends TestCase
{
    private Animal $animal;

    protected function setUp(): void
    {
        $this->animal = new Animal();
    }

    // ── Tests getters/setters ──────────────────────────────────────

    public function testSetAndGetCodeAnimal(): void
    {
        $this->animal->setCodeAnimal('A001');
        $this->assertSame('A001', $this->animal->getCodeAnimal());
    }

    public function testSetAndGetEspece(): void
    {
        $this->animal->setEspece('Bovin');
        $this->assertSame('Bovin', $this->animal->getEspece());
    }

    public function testSetAndGetRace(): void
    {
        $this->animal->setRace('Holstein');
        $this->assertSame('Holstein', $this->animal->getRace());
    }

    public function testSetAndGetSexe(): void
    {
        $this->animal->setSexe('Femelle');
        $this->assertSame('Femelle', $this->animal->getSexe());
    }

    public function testSetAndGetDateNaissance(): void
    {
        $date = new \DateTime('2020-01-15');
        $this->animal->setDateNaissance($date);
        $this->assertSame($date, $this->animal->getDateNaissance());
    }

    public function testSetAndGetIdAgriculteur(): void
    {
        $this->animal->setIdAgriculteur(42);
        $this->assertSame(42, $this->animal->getIdAgriculteur());
    }

    // ── Tests valeurs initiales ────────────────────────────────────

    public function testIdAnimalIsNullByDefault(): void
    {
        $this->assertNull($this->animal->getIdAnimal());
    }

    public function testCodeAnimalIsEmptyByDefault(): void
    {
        $this->assertSame('', $this->animal->getCodeAnimal());
    }

    public function testEspeceIsEmptyByDefault(): void
    {
        $this->assertSame('', $this->animal->getEspece());
    }

    // ── Tests logique métier ───────────────────────────────────────

    public function testSexeAcceptsMale(): void
    {
        $this->animal->setSexe('Mâle');
        $this->assertSame('Mâle', $this->animal->getSexe());
    }

    public function testSexeAcceptsFemelle(): void
    {
        $this->animal->setSexe('Femelle');
        $this->assertSame('Femelle', $this->animal->getSexe());
    }

    public function testCodeAnimalIsString(): void
    {
        $this->animal->setCodeAnimal('B999');
        $this->assertSame('B999', $this->animal->getCodeAnimal());
    }

    public function testDateNaissanceIsDateTimeInterface(): void
    {
        $date = new \DateTime('2019-06-01');
        $this->animal->setDateNaissance($date);
        $this->assertInstanceOf(\DateTimeInterface::class, $this->animal->getDateNaissance());
    }

    public function testIdAgriculteurIsInteger(): void
    {
        $this->animal->setIdAgriculteur(10);
        $this->assertIsInt($this->animal->getIdAgriculteur());
    }

    public function testSetterReturnsSelf(): void
    {
        $result = $this->animal->setCodeAnimal('X001');
        $this->assertInstanceOf(Animal::class, $result);
    }

    public function testMultipleFieldsSetCorrectly(): void
    {
        $this->animal
            ->setCodeAnimal('C100')
            ->setEspece('Ovin')
            ->setRace('Mérinos')
            ->setSexe('Mâle')
            ->setIdAgriculteur(5);

        $this->assertSame('C100', $this->animal->getCodeAnimal());
        $this->assertSame('Ovin', $this->animal->getEspece());
        $this->assertSame('Mérinos', $this->animal->getRace());
        $this->assertSame('Mâle', $this->animal->getSexe());
        $this->assertSame(5, $this->animal->getIdAgriculteur());
    }
}
