<?php

namespace App\Tests\Entity;

use App\Entity\Commande;
use App\Entity\Equipement;
use App\Entity\LigneCommande;
use PHPUnit\Framework\TestCase;

class CommandeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $before = new \DateTimeImmutable();
        $commande = new Commande();
        $after = new \DateTimeImmutable();

        $this->assertNull($commande->getId());
        $this->assertSame('', $commande->getTotal());
        $this->assertNull($commande->getAgriculteurId());
        $this->assertInstanceOf(\DateTimeImmutable::class, $commande->getDateCommande());
        $this->assertCount(0, $commande->getLignes());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $commande->getDateCommande()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $commande->getDateCommande()->getTimestamp());
    }

    public function testSettersAndGetters(): void
    {
        $commande = new Commande();
        $date = new \DateTimeImmutable('2026-05-01 11:00:00');

        $result = $commande
            ->setDateCommande($date)
            ->setTotal('1250.50')
            ->setAgriculteurId(15);

        $this->assertInstanceOf(Commande::class, $result);
        $this->assertSame($date, $commande->getDateCommande());
        $this->assertSame('1250.50', $commande->getTotal());
        $this->assertSame(15, $commande->getAgriculteurId());
    }

    public function testAddLigneLinksBothSides(): void
    {
        $commande = new Commande();
        $equipement = (new Equipement())
            ->setId_equipement(4)
            ->setNom('Tracteur')
            ->setType('Tracteur')
            ->setPrix('1000')
            ->setQuantite(2);

        $ligne = (new LigneCommande())
            ->setEquipement($equipement)
            ->setQuantite(2)
            ->setPrixUnitaire('1000.00')
            ->setTotalLigne('2000.00');

        $commande->addLigne($ligne);

        $this->assertCount(1, $commande->getLignes());
        $this->assertTrue($commande->getLignes()->contains($ligne));
        $this->assertSame($commande, $ligne->getCommande());
    }

    public function testAddLigneDoesNotDuplicateSameInstance(): void
    {
        $commande = new Commande();
        $ligne = new LigneCommande();

        $commande->addLigne($ligne);
        $commande->addLigne($ligne);

        $this->assertCount(1, $commande->getLignes());
    }

    public function testRemoveLigneUnlinksRelation(): void
    {
        $commande = new Commande();
        $ligne = new LigneCommande();
        $commande->addLigne($ligne);

        $commande->removeLigne($ligne);

        $this->assertCount(0, $commande->getLignes());
        $this->assertNull($ligne->getCommande());
    }
}
