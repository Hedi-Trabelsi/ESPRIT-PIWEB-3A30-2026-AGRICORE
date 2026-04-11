<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiController extends AbstractController
{
    public function __construct(private HttpClientInterface $httpClient) {}

    #[Route('/back/ai/generate-description', name: 'back_ai_generate_description', methods: ['POST'])]
    public function generateDescription(Request $request): JsonResponse
    {
        $titre     = trim($request->request->get('titre', ''));
        $lieu      = trim($request->request->get('lieu', ''));
        $dateDebut = trim($request->request->get('date_debut', ''));
        $prix      = trim($request->request->get('prix', ''));
        $capacite  = trim($request->request->get('capacite', ''));

        if (empty($titre)) {
            return new JsonResponse(['error' => 'Le titre est requis.'], 400);
        }

        $prompt = "Tu es un assistant spécialisé dans les événements agricoles. "
            . "Génère une description professionnelle, engageante et concise (3-4 phrases) pour l'événement suivant :\n"
            . "- Titre : {$titre}\n"
            . (!empty($lieu)      ? "- Lieu : {$lieu}\n" : '')
            . (!empty($dateDebut) ? "- Date : {$dateDebut}\n" : '')
            . (!empty($prix)      ? "- Prix : {$prix} DT\n" : '')
            . (!empty($capacite)  ? "- Capacité : {$capacite} personnes\n" : '')
            . "Réponds uniquement avec la description, sans titre ni introduction. En français.";

        try {
            $response = $this->httpClient->request('POST', 'https://text.pollinations.ai/', [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => [
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'model'  => 'openai',
                    'seed'   => 42,
                    'jsonMode' => false,
                ],
                'timeout' => 30,
            ]);

            $text = trim($response->getContent());

            if (empty($text)) {
                return new JsonResponse(['error' => 'Réponse vide de l\'IA.'], 500);
            }

            return new JsonResponse(['description' => $text]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }
}
