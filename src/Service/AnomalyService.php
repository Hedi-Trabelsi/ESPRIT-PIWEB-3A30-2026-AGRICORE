<?php

namespace App\Service;

use App\Entity\Depense;

class AnomalyService
{
    /**
     * @param Depense[] $history
     * @param Depense $candidate
     * @return array
     */
    public function analyzeDepense(array $history, Depense $candidate): array
    {
        $values = array_map(fn(Depense $d) => $d->getMontant(), $history);
        $count = count($values);

        $mean = $count > 0 ? array_sum($values) / $count : 0.0;

        $variance = 0.0;
        foreach ($values as $v) {
            $variance += pow($v - $mean, 2);
        }

        $std = $count > 1 ? sqrt($variance / ($count - 1)) : 0.0;

        $zScore = $std > 0 ? abs(($candidate->getMontant() - $mean) / $std) : 0.0;

        $isAnomaly = ($count >= 3 && $zScore > 2.0) || ($std == 0 && $count > 0 && $candidate->getMontant() > $mean * 1.5);

        return [
            'isAnomaly' => $isAnomaly,
            'score' => $zScore,
            'lowerBound' => $std > 0 ? $mean - 2 * $std : null,
            'upperBound' => $std > 0 ? $mean + 2 * $std : null,
            'mean' => $mean,
            'std' => $std,
            'message' => $isAnomaly ? "Dépense détectée comme anormale" : "Dépense normale"
        ];
    }

    /**
     * @param Depense[] $allDepenses
     * @return array
     */
    public function analyzeAll(array $allDepenses): array
    {
        $results = [];
        foreach ($allDepenses as $index => $candidate) {
            $history = $allDepenses;
            unset($history[$index]);
            $history = array_values($history);

            $analysis = $this->analyzeDepense($history, $candidate);
            $results[] = [
                'depense' => $candidate,
                'analysis' => $analysis
            ];
        }

        return $results;
    }
}
