<?php

namespace App\Tests\Service;

use App\Entity\Vente;
use App\Service\ForecastService;
use PHPUnit\Framework\TestCase;

class ForecastServiceTest extends TestCase
{
    private ForecastService $forecastService;

    protected function setUp(): void
    {
        $this->forecastService = new ForecastService();
    }

    public function testForecastUserSalesWithData(): void
    {
        $ventes = [];
        $dates = ['2024-01-01', '2024-02-01', '2024-03-01'];
        $caValues = [1000, 1500, 1200];
        
        foreach ($dates as $index => $dateStr) {
            $vente = new Vente();
            $vente->setDate(new \DateTime($dateStr));
            $vente->setChiffreAffaires($caValues[$index]);
            $ventes[] = $vente;
        }

        $result = $this->forecastService->forecastUserSales($ventes);

        $this->assertArrayHasKey('history', $result);
        $this->assertArrayHasKey('forecast', $result);
        $this->assertArrayHasKey('alerts', $result);
        $this->assertArrayHasKey('advice', $result);
        $this->assertArrayHasKey('nextMonthValue', $result);

        $this->assertCount(3, $result['history']);
        $this->assertCount(3, $result['forecast']);
    }

    public function testForecastUserSalesWithEmptyData(): void
    {
        $ventes = [];
        $result = $this->forecastService->forecastUserSales($ventes);

        $this->assertCount(0, $result['history']);
        $this->assertCount(3, $result['forecast']);
    }

    public function testNextMonthValue(): void
    {
        $ventes = [];
        $dates = ['2024-01-01', '2024-02-01', '2024-03-01'];
        $caValues = [1000, 1500, 1200];
        
        foreach ($dates as $index => $dateStr) {
            $vente = new Vente();
            $vente->setDate(new \DateTime($dateStr));
            $vente->setChiffreAffaires($caValues[$index]);
            $ventes[] = $vente;
        }

        $result = $this->forecastService->forecastUserSales($ventes);

        $this->assertGreaterThanOrEqual(0, $result['nextMonthValue']);
    }

    public function testAdviceWithGrowth(): void
    {
        $ventes = [];
        $dates = ['2024-01-01', '2024-02-01', '2024-03-01'];
        $caValues = [100, 200, 500];

        foreach ($dates as $index => $dateStr) {
            $vente = new Vente();
            $vente->setDate(new \DateTime($dateStr));
            $vente->setChiffreAffaires($caValues[$index]);
            $ventes[] = $vente;
        }

        $result = $this->forecastService->forecastUserSales($ventes);

        $this->assertNotEmpty($result['advice']);
    }

    public function testAdviceWithDecline(): void
    {
        $ventes = [];
        $dates = ['2024-01-01', '2024-02-01', '2024-03-01'];
        $caValues = [500, 400, 100];

        foreach ($dates as $index => $dateStr) {
            $vente = new Vente();
            $vente->setDate(new \DateTime($dateStr));
            $vente->setChiffreAffaires($caValues[$index]);
            $ventes[] = $vente;
        }

        $result = $this->forecastService->forecastUserSales($ventes);

        $this->assertNotEmpty($result['advice']);
    }

    public function testCustomHorizonMonths(): void
    {
        $ventes = [];
        $dates = ['2024-01-01', '2024-02-01'];
        $caValues = [100, 150];
        
        foreach ($dates as $index => $dateStr) {
            $vente = new Vente();
            $vente->setDate(new \DateTime($dateStr));
            $vente->setChiffreAffaires($caValues[$index]);
            $ventes[] = $vente;
        }

        $result = $this->forecastService->forecastUserSales($ventes, 6);
        
        $this->assertCount(6, $result['forecast']);
    }
}
