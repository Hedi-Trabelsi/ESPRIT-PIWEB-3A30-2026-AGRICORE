<?php

namespace App\Tests\Service;

use App\Entity\Animal;
use App\Service\AnimalManager;
use PHPUnit\Framework\TestCase;

class AnimalManagerTest extends TestCase
{
    private AnimalManager $manager;

    protected function setUp(): void
    {
        $this->manager = new AnimalManager();
    }

    // ── Test animal valide ─────────────────────────────────────────

    public function testValidAnimal(): void
    {
        $animal = new Animal();
        $animal->setCodeAnimal('A001')
               ->setEspece('Bovin')
               ->setRace('Holstein')
               ->setSexe('Femelle')
               ->setDateNaissance(new \DateTime('2020-01-15'));

        $this->assertTrue($this->manager->validate($animal));
    }

    // ── Règle 1 : Code animal obligatoire ─────────────────────────

    public function testCodeAnimalObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le code animal est obligatoire.');

        $animal = new Animal();
        $animal->setEspece('Bovin')->setSexe('Femelle');

        $this->manager->validate($animal);
    }

    public function testCodeAnimalVide(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $animal = new Animal();
        $animal->setCodeAnimal('')->setEspece('Bovin');

        $this->manager->validate($animal);
    }

    // ── Règle 2 : Espèce obligatoire ──────────────────────────────

    public function testEspeceObligatoire(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("L'espèce est obligatoire.");

        $animal = new Animal();
        $animal->setCodeAnimal('A001')->setSexe('Mâle');

        $this->manager->validate($animal);
    }

    // ── Règle 3 : Sexe valide ─────────────────────────────────────

    public function testSexeInvalide(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Le sexe doit être 'Mâle' ou 'Femelle'.");

        $animal = new Animal();
        $animal->setCodeAnimal('A001')
               ->setEspece('Bovin')
               ->setSexe('Inconnu');

        $this->manager->validate($animal);
    }

    public function testSexeMaleValide(): void
    {
        $animal = new Animal();
        $animal->setCodeAnimal('A002')
               ->setEspece('Ovin')
               ->setSexe('Mâle');

        $this->assertTrue($this->manager->validate($animal));
    }

    // ── Règle 4 : Date de naissance pas dans le futur ─────────────

    public function testDateNaissanceFutur(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de naissance ne peut pas être dans le futur.');

        $animal = new Animal();
        $animal->setCodeAnimal('A003')
               ->setEspece('Caprin')
               ->setDateNaissance(new \DateTime('+1 year'));

        $this->manager->validate($animal);
    }

    public function testDateNaissancePasseValide(): void
    {
        $animal = new Animal();
        $animal->setCodeAnimal('A004')
               ->setEspece('Porcin')
               ->setDateNaissance(new \DateTime('2019-06-01'));

        $this->assertTrue($this->manager->validate($animal));
    }
}
