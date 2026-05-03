<?php

namespace App\Service;

use App\Entity\Equipement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EquipmentNewsService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getNewsForEquipment(Equipement $equipement, int $limit = 3): array
    {
        $queries = array_filter([
            $equipement->getNom() . ' agriculture',
            $equipement->getType() . ' agricole',
        ]);

        foreach ($queries as $query) {
            $items = $this->fetchGoogleNewsRss($query, $limit);
            if ($items !== []) {
                return $items;
            }
        }

        return [[
            'title' => 'Aucune actualite distante disponible pour le moment',
            'source' => 'Agricore',
            'link' => null,
            'publishedAt' => null,
            'description' => 'Le flux d\'actualites n\'a pas pu etre charge. Vous pourrez brancher une API news plus tard sans changer l\'interface.',
        ]];
    }

    private function fetchGoogleNewsRss(string $query, int $limit): array
    {
        try {
            $url = 'https://news.google.com/rss/search?q=' . rawurlencode($query) . '&hl=fr&gl=FR&ceid=FR:fr';
            $xml = $this->httpClient->request('GET', $url)->getContent();

            $feed = @simplexml_load_string($xml);
            if (!$feed || !isset($feed->channel->item)) {
                return [];
            }

            $results = [];
            foreach ($feed->channel->item as $item) {
                $results[] = [
                    'title' => trim((string) $item->title),
                    'source' => trim((string) $item->source) ?: 'Google News',
                    'link' => trim((string) $item->link) ?: null,
                    'publishedAt' => trim((string) $item->pubDate) ?: null,
                    'description' => 'Article detecte autour de ' . $query . '.',
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }

            return $results;
        } catch (\Throwable) {
            return [];
        }
    }
}
