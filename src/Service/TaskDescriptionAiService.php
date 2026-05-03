<?php

namespace App\Service;

use App\Entity\Maintenance;
use App\Entity\User;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TaskDescriptionAiService
{
    private const URL = 'https://api.groq.com/openai/v1/chat/completions';
    private const MODEL = 'llama-3.3-70b-versatile';

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    public function generateForMaintenance(Maintenance $maintenance, string $taskName, ?User $technicien = null): string
    {
        $apiKey = $_ENV['GROQ_API_KEY'] ?? $_SERVER['GROQ_API_KEY'] ?? getenv('GROQ_API_KEY');

        if (!$apiKey) {
            throw new \RuntimeException('La variable d\'environnement GROQ_API_KEY est introuvable.');
        }

        $payload = [
            'model' => self::MODEL,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => sprintf(
                        "Tu es un expert en maintenance agricole.

Tu dois générer une description technique STRICTEMENT basée uniquement sur les données fournies.

⚠️ RÈGLES IMPORTANTES :
- Interdiction d'ajouter des phrases générales (formation, sécurité, conseils génériques)
- Interdiction d'ajouter des recommandations hors contexte
- Interdiction d'inventer des informations
- Interdiction de faire une conclusion
- Répondre uniquement avec les sections demandées

FORMAT OBLIGATOIRE :

[RAPPEL] : résumé court de la tâche

--- IA DIAGNOSTIC ---

CAUSE PROBABLE : analyse technique basée uniquement sur les données fournies

GRAVITÉ : faible / modérée / critique avec justification factuelle

SOLUTION : actions correctives directement liées à la tâche

Contexte :
- Nom de la tâche : %s
- Maintenance : %s
- Type de maintenance : %s
- Équipement : %s
- Description de la maintenance : %s",
                        $taskName !== '' ? $taskName : 'Non renseigné',
                        $maintenance->getNomMaintenance(),
                        $maintenance->getType(),
                        $maintenance->getEquipement(),
                        $maintenance->getDescription()
                    ),
                ],
            ],
            'temperature' => 0.5,
        ];

        $response = $this->httpClient->request('POST', self::URL, [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $data = $response->toArray(false);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new \RuntimeException('Réponse IA invalide.');
        }

        return trim($data['choices'][0]['message']['content']);
    }
}