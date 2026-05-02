<?php

namespace App\Tests\Entity;

use App\Entity\Equipement;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class EquipementTest extends TestCase
{
    private Equipement $equipement;

    protected function setUp(): void
    {
        $this->equipement = new Equipement();
    }

    public function testDefaultValues(): void
    {
        $this->assertNull($this->equipement->getId());
        $this->assertNull($this->equipement->getNom());
        $this->assertNull($this->equipement->getType());
        $this->assertNull($this->equipement->getPrix());
        $this->assertNull($this->equipement->getQuantite());
        $this->assertNull($this->equipement->getImageFilename());
        $this->assertNull($this->equipement->getImageFile());
        $this->assertNull($this->equipement->getUpdatedAt());
        $this->assertTrue($this->equipement->isActive());
        $this->assertCount(0, $this->equipement->getPaniers());
    }

    public function testSettersAndGetters(): void
    {
        $updatedAt = new \DateTimeImmutable('2026-05-01 10:30:00');

        $result = $this->equipement
            ->setId_equipement(8)
            ->setNom('Moissonneuse')
            ->setType('Recolte')
            ->setPrix('18500')
            ->setQuantite(6)
            ->setImageFilename('moissonneuse.jpg')
            ->setUpdatedAt($updatedAt)
            ->setIsActive(false);

        $this->assertInstanceOf(Equipement::class, $result);
        $this->assertSame(8, $this->equipement->getId());
        $this->assertSame(8, $this->equipement->getId_equipement());
        $this->assertSame(8, $this->equipement->getIdEquipement());
        $this->assertSame('Moissonneuse', $this->equipement->getNom());
        $this->assertSame('Recolte', $this->equipement->getType());
        $this->assertSame('18500', $this->equipement->getPrix());
        $this->assertSame(6, $this->equipement->getQuantite());
        $this->assertSame('moissonneuse.jpg', $this->equipement->getImageFilename());
        $this->assertSame($updatedAt, $this->equipement->getUpdatedAt());
        $this->assertFalse($this->equipement->isActive());
    }

    public function testSetImageFileUpdatesTimestampWhenFileExists(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'equipement_test_');
        $file = new File($tempFile);

        $before = new \DateTimeImmutable();
        $this->equipement->setImageFile($file);
        $after = new \DateTimeImmutable();

        $this->assertSame($file, $this->equipement->getImageFile());
        $updatedAt = $this->equipement->getUpdatedAt();
        $this->assertNotNull($updatedAt);
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $updatedAt->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $updatedAt->getTimestamp());

        @unlink($tempFile);
    }

    public function testSetImageFileWithNullDoesNotChangeTimestamp(): void
    {
        $this->equipement->setUpdatedAt(new \DateTimeImmutable('2026-04-30 09:00:00'));

        $this->equipement->setImageFile(null);

        $this->assertSame('2026-04-30 09:00:00', $this->equipement->getUpdatedAt()?->format('Y-m-d H:i:s'));
        $this->assertNull($this->equipement->getImageFile());
    }
}
