<?php

namespace App\Tests\Entity;

use App\Entity\Commande;
use App\Entity\Equipement;
use App\Entity\LigneCommande;
use PHPUnit\Framework\TestCase;

class LigneCommandeTest extends TestCase
{
    private LigneCommande $ligneCommande;

    protected function setUp(): void
    {
        $this->ligneCommande = new LigneCommande();
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->ligneCommande->getId());
        $this->assertNull($this->ligneCommande->getCommande());
        $this->assertNull($this->ligneCommande->getEquipement());
        $this->assertSame(0, $this->ligneCommande->getQuantite());
        $this->assertSame('', $this->ligneCommande->getPrixUnitaire());
        $this->assertSame('', $this->ligneCommande->getTotalLigne());
    }

    public function testSettersAndGetters(): void
    {
        $commande = new Commande();
        $equipement = (new Equipement())
            ->setId_equipement(11)
            ->setNom('Pompe')
            ->setType('Irrigation')
            ->setPrix('450')
            ->setQuantite(8);

        $result = $this->ligneCommande
            ->setCommande($commande)
            ->setEquipement($equipement)
            ->setQuantite(3)
            ->setPrixUnitaire('450.00')
            ->setTotalLigne('1350.00');

        $this->assertInstanceOf(LigneCommande::class, $result);
        $this->assertSame($commande, $this->ligneCommande->getCommande());
        $this->assertSame($equipement, $this->ligneCommande->getEquipement());
        $this->assertSame(3, $this->ligneCommande->getQuantite());
        $this->assertSame('450.00', $this->ligneCommande->getPrixUnitaire());
        $this->assertSame('1350.00', $this->ligneCommande->getTotalLigne());
    }

    public function testCanChangeCommandeReference(): void
    {
        $commande1 = new Commande();
        $commande2 = new Commande();

        $this->ligneCommande->setCommande($commande1);
        $this->assertSame($commande1, $this->ligneCommande->getCommande());

        $this->ligneCommande->setCommande($commande2);
        $this->assertSame($commande2, $this->ligneCommande->getCommande());
    }

    public function testCanUnsetCommandeReference(): void
    {
        $this->ligneCommande->setCommande(new Commande());

        $this->ligneCommande->setCommande(null);

        $this->assertNull($this->ligneCommande->getCommande());
    }
}
