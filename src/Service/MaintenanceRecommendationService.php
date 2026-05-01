<?php

namespace App\Service;

use App\Entity\Maintenance;
use App\Entity\User;
use App\Repository\TacheRepository;
use Psr\Log\LoggerInterface;

class MaintenanceRecommendationService
{
    public function __construct(
        private readonly MaintenanceProximityService $proximityService,
        private readonly TacheRepository $tacheRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param Maintenance[] $maintenances
     *
     * @return array{
     *     recommendations: array<int, array<string, mixed>>,
     *     enabled: bool,
     *     message: string,
     *     meta: array<string, mixed>
     * }
     */
    public function recommendForTechnician(User $technician, array $maintenances, ?string $technicianAddress, int $limit = 3): array
    {
        if ($maintenances === []) {
            return [
                'recommendations' => [],
                'enabled' => false,
                'message' => 'Aucune maintenance disponible pour la recommandation.',
                'meta' => [],
            ];
        }

        $proximity = $this->proximityService->sortByRoadDistance($maintenances, $technicianAddress);
        $sortedMaintenances = $proximity['maintenances'];
        $distances = $proximity['distances'];

        $historyCounts = [];
        foreach ($sortedMaintenances as $maintenance) {
            $farmer = $maintenance->getId_agriculteur();
            if (!$farmer) {
                $historyCounts[$maintenance->getId_maintenance()] = 0;
                continue;
            }

            $historyCounts[$maintenance->getId_maintenance()] = $this->tacheRepository->countPastTasksForTechnicianAndFarmer(
                $technician->getId(),
                $farmer->getId(),
                new \DateTimeImmutable('today')
            );
        }

        $availableDistances = array_values(array_filter(
            $distances,
            static fn ($distance): bool => is_numeric($distance)
        ));

        $minDistance = $availableDistances !== [] ? (float) min($availableDistances) : null;
        $maxDistance = $availableDistances !== [] ? (float) max($availableDistances) : null;
        $maxHistory = $historyCounts !== [] ? max($historyCounts) : 0;

        $recommendations = [];
        foreach ($sortedMaintenances as $maintenance) {
            $maintenanceId = $maintenance->getId_maintenance();
            $distance = $distances[$maintenanceId] ?? null;
            $historyCount = $historyCounts[$maintenanceId] ?? 0;

            $distanceScore = $this->computeDistanceScore($distance, $minDistance, $maxDistance, $proximity['enabled']);
            $historyScore = $this->computeHistoryScore($historyCount, $maxHistory);
            $finalScore = round((0.7 * $distanceScore + 0.3 * $historyScore) * 100, 1);

            $recommendations[] = [
                'id' => $maintenanceId,
                'maintenanceId' => $maintenanceId,
                'maintenanceName' => $maintenance->getNomMaintenance(),
                'priority' => $maintenance->getPriorite(),
                'equipment' => $maintenance->getEquipement(),
                'location' => $maintenance->getLieu(),
                'description' => $maintenance->getDescription(),
                'score' => $finalScore,
                'distanceKm' => is_numeric($distance) ? (float) $distance : null,
                'historyCount' => $historyCount,
                'farmerName' => $this->formatFarmerName($maintenance),
                'reasons' => $this->buildReasons($distance, $historyCount, $proximity['enabled']),
            ];
        }

        usort($recommendations, static function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                return ($left['distanceKm'] ?? PHP_FLOAT_MAX) <=> ($right['distanceKm'] ?? PHP_FLOAT_MAX);
            }

            return $right['score'] <=> $left['score'];
        });

        $recommendations = array_slice($recommendations, 0, max(1, $limit));

        if (!$proximity['enabled']) {
            $this->logger->info('Maintenance recommendation computed without distance data.', [
                'technicianId' => $technician->getId(),
            ]);
        }

        return [
            'recommendations' => $recommendations,
            'enabled' => true,
            'message' => 'Recommandations calculées avec succès.',
            'meta' => [
                'distanceEnabled' => $proximity['enabled'],
                'totalCandidates' => count($sortedMaintenances),
                'limit' => count($recommendations),
            ],
        ];
    }

    private function computeDistanceScore(?float $distanceKm, ?float $minDistance, ?float $maxDistance, bool $distanceEnabled): float
    {
        if (!$distanceEnabled || $distanceKm === null) {
            return 0.5;
        }

        if ($minDistance === null || $maxDistance === null || $minDistance === $maxDistance) {
            return 1.0;
        }

        $normalized = 1 - (($distanceKm - $minDistance) / max(0.0001, $maxDistance - $minDistance));

        return max(0.0, min(1.0, $normalized));
    }

    private function computeHistoryScore(int $historyCount, int $maxHistory): float
    {
        if ($maxHistory <= 0) {
            return 0.0;
        }

        return max(0.0, min(1.0, $historyCount / $maxHistory));
    }

    private function formatFarmerName(Maintenance $maintenance): string
    {
        $farmer = $maintenance->getId_agriculteur();
        if (!$farmer) {
            return 'Inconnu';
        }

        $fullName = trim((string) $farmer->getPrenom() . ' ' . (string) $farmer->getNom());

        return $fullName !== '' ? $fullName : 'Inconnu';
    }

    /**
     * @return string[]
     */
    private function buildReasons(?float $distanceKm, int $historyCount, bool $distanceEnabled): array
    {
        $reasons = [];

        if ($distanceEnabled && $distanceKm !== null) {
            $reasons[] = sprintf('Distance estimée: %.2f km.', $distanceKm);
        } elseif (!$distanceEnabled) {
            $reasons[] = 'Distance indisponible pour cette session.';
        } else {
            $reasons[] = 'Adresse technicien ou géocodage indisponible.';
        }

        if ($historyCount > 0) {
            $reasons[] = sprintf('Déjà travaillé %d fois avec cet agriculteur.', $historyCount);
        } else {
            $reasons[] = 'Aucun historique de travail trouvé avec cet agriculteur.';
        }

        return $reasons;
    }
}
