<?php

namespace App\Service;

use App\Entity\Equipement;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class EquipmentAiService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
        private readonly string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions',
        private readonly string $model = 'llama-3.1-8b-instant',
    ) {
    }

    public function generateInsights(Equipement $equipement): array
    {
        $fallback = $this->buildFallbackInsights($equipement);

        if (!$this->apiKey) {
            return $fallback + ['provider' => 'local'];
        }

        try {
            $prompt = sprintf(
                "Tu es un expert en equipements agricoles. Donne une analyse concise en francais pour un equipement nomme %s, de type %s, au prix de %s TND avec un stock de %d unite(s). Retourne du texte structure en 3 courts paragraphes: usage, points de vigilance, conseil d'achat.",
                $equipement->getNom(),
                $equipement->getType(),
                $equipement->getPrix(),
                $equipement->getQuantite()
            );

            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'temperature' => 0.5,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu rediges des fiches produit agricoles utiles, precises et orientees action.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]);

            $data = $response->toArray(false);
            $content = trim((string) ($data['choices'][0]['message']['content'] ?? ''));

            if ($content !== '') {
                return [
                    'summary' => $content,
                    'bullets' => $fallback['bullets'],
                    'score' => $fallback['score'],
                    'provider' => 'groq',
                ];
            }
        } catch (\Throwable) {
        }

        return $fallback + ['provider' => 'local'];
    }

    private function buildFallbackInsights(Equipement $equipement): array
    {
        $price = (float) $equipement->getPrix();
        $quantity = (int) $equipement->getQuantite();
        $type = mb_strtolower((string) $equipement->getType());

        $usage = match (true) {
            str_contains($type, 'tract') => 'Ideal pour mecaniser les travaux lourds et gagner du temps sur les parcelles et le transport.',
            str_contains($type, 'irrig') => 'Permet de mieux regulariser l\'apport en eau et de limiter les pertes sur les cultures sensibles.',
            str_contains($type, 'pulver') => 'Aide a optimiser le traitement des cultures avec plus de regularite et de precision.',
            str_contains($type, 'sem') => 'Convient pour standardiser les semis et mieux repartir les graines sur le terrain.',
            default => 'Peut renforcer la productivite de l\'exploitation si son usage est bien aligne avec les besoins terrain.',
        };

        $risk = $quantity <= 0
            ? 'Le produit est actuellement en rupture: planifier un reassort ou une alternative est prioritaire.'
            : ($quantity < 5
                ? 'Le stock est faible: une anticipation des commandes evitera les ruptures pendant les pics d\'activite.'
                : 'Le stock est confortable pour couvrir les besoins a court terme.');

        $priceAdvice = $price >= 10000
            ? 'Investissement important: il faut verifier le ROI, la maintenance et la frequence d\'utilisation.'
            : ($price >= 3000
                ? 'Budget intermediaire: interessant si l\'equipement remplace des taches repetitives ou couteuses.'
                : 'Cout accessible: bonne option pour une montee en gamme progressive du materiel.');

        $score = max(55, min(96, 70 + ($quantity > 10 ? 8 : 0) + ($price < 5000 ? 6 : 0) - ($quantity === 0 ? 18 : 0)));

        return [
            'summary' => $usage . "\n\n" . $risk . "\n\n" . $priceAdvice,
            'bullets' => [
                'Priorite operationnelle: ' . ($quantity < 5 ? 'surveiller le stock et les delais d\'approvisionnement.' : 'produit exploitable immediatement.'),
                'Positionnement budget: ' . number_format($price, 2, '.', ' ') . ' TND.',
                'Recommandation: comparer avec des equipements du meme type pour confirmer la valeur terrain.',
            ],
            'score' => $score,
        ];
    }
}
