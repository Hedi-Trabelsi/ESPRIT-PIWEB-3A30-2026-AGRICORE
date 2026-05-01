<?php

namespace App\Service;

use App\Entity\Vente;
use DateTime;

class ForecastService
{
    public function forecastUserSales(array $ventes, int $horizonMonths = 3): array
    {
        $monthSum = [];
        foreach ($ventes as $v) {
            if ($v->getDate() === null) continue;
            $ym = $v->getDate()->format('Y-m');
            $monthSum[$ym] = ($monthSum[$ym] ?? 0.0) + $v->getChiffreAffaires();
        }

        ksort($monthSum);
        $months = array_keys($monthSum);
        $series = array_values($monthSum);

        $alpha = 0.5;
        $level = empty($series) ? 0.0 : $series[0];
        $fitted = [];
        foreach ($series as $y) {
            $level = $alpha * $y + (1 - $alpha) * $level;
            $fitted[] = $level;
        }

        $mse = 0.0;
        foreach ($series as $i => $y) {
            $e = $y - $fitted[$i];
            $mse += $e * $e;
        }
        $sigma = !empty($series) ? sqrt($mse / max(1, count($series))) : 0.0;

        $history = [];
        foreach ($months as $i => $ym) {
            $history[] = [
                'date' => $ym,
                'value' => $series[$i],
                'lower' => null,
                'upper' => null
            ];
        }

        $forecast = [];
        $lastYM = empty($months) ? new DateTime() : new DateTime($months[count($months) - 1] . '-01');
        $lastLevel = empty($fitted) ? 0.0 : $fitted[count($fitted) - 1];

        for ($h = 1; $h <= $horizonMonths; $h++) {
            $lastYM->modify('+1 month');
            $forecastVal = $lastLevel;
            $lower = $forecastVal - 1.96 * $sigma;
            $upper = $forecastVal + 1.96 * $sigma;

            $forecast[] = [
                'date' => $lastYM->format('Y-m'),
                'value' => round($forecastVal, 2),
                'lower' => round($lower, 2),
                'upper' => round($upper, 2)
            ];
        }

        $alerts = [];
        $advice = "Maintenez votre stratégie actuelle et suivez vos indicateurs de près.";

        if (!empty($forecast)) {
            $nextMonth = $forecast[0]['value'];
            $lastHistory = !empty($series) ? $series[count($series) - 1] : 0;

            if ($nextMonth > $lastHistory * 1.1) {
                $advice = "Excellente tendance ! Pensez à réinvestir vos bénéfices pour accélérer votre croissance.";
            } elseif ($nextMonth < $lastHistory * 0.9) {
                $advice = "Ventes en baisse prévues. Analysez vos canaux d'acquisition et fidélisez vos clients.";
            }

            foreach ($forecast as $f) {
                if ($f['upper'] < 0) {
                    $alerts[] = "Risque: ventes négatives prévues pour " . $f['date'];
                    $advice = "Trop de dépenses: réduis coûts variables ou décale dépenses non urgentes.";
                    break;
                }
            }
        }

        return [
            'history' => $history,
            'forecast' => $forecast,
            'alerts' => $alerts,
            'advice' => $advice,
            'nextMonthValue' => !empty($forecast) ? $forecast[0]['value'] : 0
        ];
    }
}
