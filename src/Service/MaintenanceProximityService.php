<?php

namespace App\Service;

use App\Entity\Maintenance;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MaintenanceProximityService
{
    private const GEO_CACHE_TTL = 86400;
    private const DISTANCE_CACHE_TTL = 3600;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        #[Autowire(service: 'app.geocoder.nominatim')]
        private readonly Provider $geocoderProvider,
        private readonly string $osrmBaseUrl = 'https://router.project-osrm.org'
    ) {
    }

    /**
     * @param Maintenance[] $maintenances
     *
     * @return array{maintenances: Maintenance[], distances: array<int, ?float>, enabled: bool}
     */
    public function sortByRoadDistance(array $maintenances, ?string $technicianAddress): array
    {
        $distances = [];

        if (!$technicianAddress || trim($technicianAddress) === '') {
            return [
                'maintenances' => $maintenances,
                'distances' => $distances,
                'enabled' => false,
            ];
        }

        $techCoordinates = $this->geocodeAddress($technicianAddress);
        if ($techCoordinates === null) {
            return [
                'maintenances' => $maintenances,
                'distances' => $distances,
                'enabled' => false,
            ];
        }

        foreach ($maintenances as $maintenance) {
            $maintenanceId = $maintenance->getId_maintenance();
            $lieu = trim((string) $maintenance->getLieu());

            if ($lieu === '') {
                $distances[$maintenanceId] = null;
                continue;
            }

            $maintenanceCoordinates = $this->geocodeAddress($lieu);
            if ($maintenanceCoordinates === null) {
                $distances[$maintenanceId] = null;
                continue;
            }

            $distances[$maintenanceId] = $this->getRoadDistanceKm($techCoordinates, $maintenanceCoordinates);
        }

        $sorted = $maintenances;
        usort($sorted, function (Maintenance $left, Maintenance $right) use ($distances): int {
            $leftDistance = $distances[$left->getId_maintenance()] ?? null;
            $rightDistance = $distances[$right->getId_maintenance()] ?? null;

            if ($leftDistance === null && $rightDistance === null) {
                return strcmp((string) $left->getLieu(), (string) $right->getLieu());
            }

            if ($leftDistance === null) {
                return 1;
            }

            if ($rightDistance === null) {
                return -1;
            }

            return $leftDistance <=> $rightDistance;
        });

        return [
            'maintenances' => $sorted,
            'distances' => $distances,
            'enabled' => true,
        ];
    }

    /**
     * @return array{lat: float, lon: float}|null
     */
    private function geocodeAddress(string $address): ?array
    {
        $normalizedAddress = trim($address);
        if ($normalizedAddress === '') {
            return null;
        }

        $cacheKey = 'geo_' . md5(strtolower($normalizedAddress));

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($normalizedAddress): ?array {
            $item->expiresAfter(self::GEO_CACHE_TTL);

            try {
                $result = $this->geocoderProvider->geocodeQuery(GeocodeQuery::create($normalizedAddress)->withLimit(1));
                if (count($result) === 0) {
                    return null;
                }

                $coordinates = $result->first()->getCoordinates();
                if ($coordinates === null) {
                    return null;
                }

                return [
                    'lat' => $coordinates->getLatitude(),
                    'lon' => $coordinates->getLongitude(),
                ];
            } catch (\Throwable $e) {
                $this->logger->warning('Geocoding failed for address', [
                    'address' => $normalizedAddress,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * @param array{lat: float, lon: float} $origin
     * @param array{lat: float, lon: float} $destination
     */
    private function getRoadDistanceKm(array $origin, array $destination): ?float
    {
        $cacheKey = sprintf(
            'road_%s_%s',
            md5($origin['lat'] . ',' . $origin['lon']),
            md5($destination['lat'] . ',' . $destination['lon'])
        );

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($origin, $destination): ?float {
            $item->expiresAfter(self::DISTANCE_CACHE_TTL);

            $coordinates = sprintf(
                '%s,%s;%s,%s',
                $origin['lon'],
                $origin['lat'],
                $destination['lon'],
                $destination['lat']
            );

            $url = rtrim($this->osrmBaseUrl, '/') . '/route/v1/driving/' . $coordinates;

            try {
                $response = $this->httpClient->request('GET', $url, [
                    'query' => [
                        'overview' => 'false',
                        'alternatives' => 'false',
                        'steps' => 'false',
                    ],
                    'timeout' => 8,
                ]);

                $payload = $response->toArray(false);
                $distanceMeters = $payload['routes'][0]['distance'] ?? null;

                if (!is_numeric($distanceMeters)) {
                    return null;
                }

                return round(((float) $distanceMeters) / 1000, 2);
            } catch (\Throwable $e) {
                $this->logger->warning('OSRM distance request failed', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }
}
