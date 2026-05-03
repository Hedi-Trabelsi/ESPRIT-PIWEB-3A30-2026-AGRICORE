<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExchangeRateService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getRatesFromTnd(): array
    {
        try {
            $response = $this->httpClient->request('GET', 'https://api.frankfurter.app/latest?from=TND&to=EUR,USD');
            $data = $response->toArray(false);
            $rates = $data['rates'] ?? [];

            if (isset($rates['EUR'], $rates['USD'])) {
                return [
                    'EUR' => (float) $rates['EUR'],
                    'USD' => (float) $rates['USD'],
                    'source' => 'Frankfurter',
                    'updated_at' => $data['date'] ?? date('Y-m-d'),
                ];
            }
        } catch (\Throwable) {
        }

        return [
            'EUR' => 0.30,
            'USD' => 0.32,
            'source' => 'fallback',
            'updated_at' => date('Y-m-d'),
        ];
    }

    public function convertFromTnd(float $amount): array
    {
        $rates = $this->getRatesFromTnd();

        return [
            'TND' => $amount,
            'EUR' => $amount * $rates['EUR'],
            'USD' => $amount * $rates['USD'],
            'meta' => $rates,
        ];
    }
}
