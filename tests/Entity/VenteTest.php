<?php

namespace App\Tests\Entity;

use App\Entity\Vente;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class VenteTest extends TestCase
{
    private Vente $vente;

    protected function setUp(): void
    {
        $this->vente = new Vente();
    }

    public function testSetAndGetIdVente(): void
    {
        $this->vente->setIdVente(1);
        $this->assertSame(1, $this->vente->getIdVente());
    }

    public function testSetAndGetPrixUnitaire(): void
    {
        $this->vente->setPrixUnitaire(100);
        $this->assertSame(100, $this->vente->getPrixUnitaire());
    }

    public function testSetAndGetQuantite(): void
    {
        $this->vente->setQuantite(5);
        $this->assertSame(5, $this->vente->getQuantite());
    }

    public function testSetAndGetChiffreAffaires(): void
    {
        $this->vente->setChiffreAffaires(500);
        $this->assertSame(500, $this->vente->getChiffreAffaires());
    }

    public function testSetAndGetDate(): void
    {
        $date = new \DateTime('2024-05-01');
        $this->vente->setDate($date);
        $this->assertSame($date, $this->vente->getDate());
    }

    public function testSetAndGetProduit(): void
    {
        $this->vente->setProduit('Blé');
        $this->assertSame('Blé', $this->vente->getProduit());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $this->vente->setUser($user);
        $this->assertSame($user, $this->vente->getUser());
    }

    public function testIdVenteIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getIdVente());
    }

    public function testPrixUnitaireIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getPrixUnitaire());
    }

    public function testQuantiteIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getQuantite());
    }

    public function testChiffreAffairesIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getChiffreAffaires());
    }

    public function testDateIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getDate());
    }

    public function testProduitIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getProduit());
    }

    public function testUserIsNullByDefault(): void
    {
        $this->assertNull($this->vente->getUser());
    }

    public function testPrixUnitaireIsInteger(): void
    {
        $this->vente->setPrixUnitaire(250);
        $this->assertIsInt($this->vente->getPrixUnitaire());
    }

    public function testQuantiteIsInteger(): void
    {
        $this->vente->setQuantite(10);
        $this->assertIsInt($this->vente->getQuantite());
    }

    public function testChiffreAffairesIsInteger(): void
    {
        $this->vente->setChiffreAffaires(1000);
        $this->assertIsInt($this->vente->getChiffreAffaires());
    }

    public function testDateIsDateTimeInterface(): void
    {
        $date = new \DateTime('2024-01-01');
        $this->vente->setDate($date);
        $this->assertInstanceOf(\DateTimeInterface::class, $this->vente->getDate());
    }

    // ── Tests des règles métier ───────────────────────────────────────

    public function testPrixUnitaireMustBeGreaterThanZero(): void
    {
        $this->vente->setPrixUnitaire(50);
        $this->assertGreaterThan(0, $this->vente->getPrixUnitaire());
    }

    public function testQuantiteCannotBeNegative(): void
    {
        $this->vente->setQuantite(10);
        $this->assertGreaterThanOrEqual(0, $this->vente->getQuantite());
    }

    public function testProduitCannotBeEmpty(): void
    {
        $this->vente->setProduit('Blé');
        $this->assertNotEmpty($this->vente->getProduit());
        $this->assertIsString($this->vente->getProduit());
    }

    public function testChiffreAffairesCalculation(): void
    {
        $prixUnitaire = 100;
        $quantite = 5;
        $chiffreAffairesAttendu = $prixUnitaire * $quantite;
        
        $this->vente->setPrixUnitaire($prixUnitaire);
        $this->vente->setQuantite($quantite);
        $this->vente->setChiffreAffaires($chiffreAffairesAttendu);
        
        $this->assertSame($chiffreAffairesAttendu, $this->vente->getChiffreAffaires());
    }
}
