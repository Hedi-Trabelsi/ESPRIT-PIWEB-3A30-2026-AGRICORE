<?php

namespace App\Tests\Service;

use App\Entity\Equipement;
use App\Service\EquipmentAiService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EquipmentAiServiceTest extends TestCase
{
    public function testGenerateInsightsReturnsLocalProviderAndStructuredPayload(): void
    {
        $service = new EquipmentAiService($this->createMock(HttpClientInterface::class));
        $equipement = $this->createEquipement('Tracteur X', 'Tracteur', '4500', 12);

        $insights = $service->generateInsights($equipement);

        $this->assertSame('local', $insights['provider']);
        $this->assertArrayHasKey('summary', $insights);
        $this->assertArrayHasKey('bullets', $insights);
        $this->assertArrayHasKey('score', $insights);
        $this->assertCount(3, $insights['bullets']);
        $this->assertStringContainsString('mecaniser', $insights['summary']);
        $this->assertSame(84, $insights['score']);
    }

    public function testGenerateInsightsWarnsWhenStockIsUnavailable(): void
    {
        $service = new EquipmentAiService($this->createMock(HttpClientInterface::class));
        $equipement = $this->createEquipement('Pulverisateur S', 'Pulverisateur', '12000', 0);

        $insights = $service->generateInsights($equipement);

        $this->assertStringContainsString('rupture', $insights['summary']);
        $this->assertStringContainsString('12 000.00 TND', $insights['bullets'][1]);
        $this->assertSame(55, $insights['score']);
    }

    public function testGenerateInsightsMentionsIntermediateBudgetForMidRangePrice(): void
    {
        $service = new EquipmentAiService($this->createMock(HttpClientInterface::class));
        $equipement = $this->createEquipement('Semoir M', 'Semoir', '3500', 4);

        $insights = $service->generateInsights($equipement);

        $this->assertStringContainsString('Budget intermediaire', $insights['summary']);
        $this->assertStringContainsString('surveiller le stock', $insights['bullets'][0]);
        $this->assertSame(76, $insights['score']);
    }

    private function createEquipement(string $nom, string $type, string $prix, int $quantite): Equipement
    {
        $equipement = new Equipement();
        $equipement->setNom($nom);
        $equipement->setType($type);
        $equipement->setPrix($prix);
        $equipement->setQuantite($quantite);

        return $equipement;
    }
}
