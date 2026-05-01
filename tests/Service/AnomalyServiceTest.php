<?php

namespace App\Tests\Service;

use App\Entity\Depense;
use App\Service\AnomalyService;
use PHPUnit\Framework\TestCase;

class AnomalyServiceTest extends TestCase
{
    private AnomalyService $anomalyService;

    protected function setUp(): void
    {
        $this->anomalyService = new AnomalyService();
    }

    public function testNormalDepense(): void
    {
        $history = [];
        $montants = [100, 150, 120, 130, 140];
        foreach ($montants as $m) {
            $d = new Depense();
            $d->setMontant($m);
            $history[] = $d;
        }

        $candidate = new Depense();
        $candidate->setMontant(135);

        $analysis = $this->anomalyService->analyzeDepense($history, $candidate);
        
        $this->assertFalse($analysis['isAnomaly']);
        $this->assertEquals('Dépense normale', $analysis['message']);
    }

    public function testAnomalyDepenseHighZScore(): void
    {
        $history = [];
        $montants = [100, 150, 120, 130, 140];
        foreach ($montants as $m) {
            $d = new Depense();
            $d->setMontant($m);
            $history[] = $d;
        }

        $candidate = new Depense();
        $candidate->setMontant(1000);

        $analysis = $this->anomalyService->analyzeDepense($history, $candidate);
        
        $this->assertTrue($analysis['isAnomaly']);
        $this->assertEquals('Dépense détectée comme anormale', $analysis['message']);
        $this->assertGreaterThan(2.0, $analysis['score']);
    }

    public function testAnomalyDepenseWithZeroStd(): void
    {
        $history = [];
        $montants = [100, 100, 100];
        foreach ($montants as $m) {
            $d = new Depense();
            $d->setMontant($m);
            $history[] = $d;
        }

        $candidate = new Depense();
        $candidate->setMontant(200);

        $analysis = $this->anomalyService->analyzeDepense($history, $candidate);
        
        $this->assertTrue($analysis['isAnomaly']);
    }

    public function testAnalyzeAll(): void
    {
        $allDepenses = [];
        $montants = [100, 150, 120, 1000];
        foreach ($montants as $m) {
            $d = new Depense();
            $d->setMontant($m);
            $allDepenses[] = $d;
        }

        $results = $this->anomalyService->analyzeAll($allDepenses);
        
        $this->assertCount(4, $results);
        $this->assertArrayHasKey('depense', $results[0]);
        $this->assertArrayHasKey('analysis', $results[0]);
    }

    public function testEmptyHistory(): void
    {
        $history = [];
        $candidate = new Depense();
        $candidate->setMontant(500);

        $analysis = $this->anomalyService->analyzeDepense($history, $candidate);
        
        $this->assertFalse($analysis['isAnomaly']);
    }
}
