<?php

namespace App\Tests\Entity;

use App\Entity\Depense;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class DepenseTest extends TestCase
{
    private Depense $depense;

    protected function setUp(): void
    {
        $this->depense = new Depense();
    }

    public function testSetAndGetIdDepense(): void
    {
        $this->depense->setIdDepense(1);
        $this->assertSame(1, $this->depense->getIdDepense());
    }

    public function testSetAndGetType(): void
    {
        $this->depense->setType('Engrais');
        $this->assertSame('Engrais', $this->depense->getType());
    }

    public function testSetAndGetMontant(): void
    {
        $this->depense->setMontant(250.50);
        $this->assertSame(250.50, $this->depense->getMontant());
    }

    public function testSetAndGetDate(): void
    {
        $date = new \DateTime('2024-05-01');
        $this->depense->setDate($date);
        $this->assertSame($date, $this->depense->getDate());
    }

    public function testSetAndGetUser(): void
    {
        $user = new User();
        $this->depense->setUser($user);
        $this->assertSame($user, $this->depense->getUser());
    }

    public function testIdDepenseIsNullByDefault(): void
    {
        $this->assertNull($this->depense->getIdDepense());
    }

    public function testTypeIsNullByDefault(): void
    {
        $this->assertNull($this->depense->getType());
    }

    public function testMontantIsNullByDefault(): void
    {
        $this->assertNull($this->depense->getMontant());
    }

    public function testDateIsNullByDefault(): void
    {
        $this->assertNull($this->depense->getDate());
    }

    public function testUserIsNullByDefault(): void
    {
        $this->assertNull($this->depense->getUser());
    }

    public function testMontantIsFloat(): void
    {
        $this->depense->setMontant(100.75);
        $this->assertIsFloat($this->depense->getMontant());
    }

    public function testDateIsDateTimeInterface(): void
    {
        $date = new \DateTime('2024-01-01');
        $this->depense->setDate($date);
        $this->assertInstanceOf(\DateTimeInterface::class, $this->depense->getDate());
    }

    // ── Tests des règles métier ───────────────────────────────────────

    public function testMontantCannotBeNegative(): void
    {
        $this->depense->setMontant(100.0);
        $this->assertSame(100.0, $this->depense->getMontant());
        $this->assertGreaterThanOrEqual(0, $this->depense->getMontant());
    }

    public function testTypeCannotBeEmpty(): void
    {
        $this->depense->setType('Engrais');
        $this->assertNotEmpty($this->depense->getType());
        $this->assertIsString($this->depense->getType());
    }

    public function testDateCannotBeInFuture(): void
    {
        $today = new \DateTime();
        $this->depense->setDate($today);
        $this->assertLessThanOrEqual(new \DateTime(), $this->depense->getDate());
    }
}
