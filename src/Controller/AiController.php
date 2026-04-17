<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use Doctrine\ORM\EntityManagerInterface;
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
        $titre    = trim($request->request->get('titre', ''));
        $lieu     = trim($request->request->get('lieu', ''));
        $dateDebut= trim($request->request->get('date_debut', ''));
        $prix     = trim($request->request->get('prix', ''));
        $capacite = trim($request->request->get('capacite', ''));

        if (empty($titre)) return new JsonResponse(['error' => 'Le titre est requis.'], 400);

        $prompt = "Tu es un assistant spécialisé dans les événements agricoles. "
            . "Génère une description professionnelle, engageante et concise (3-4 phrases) :\n"
            . "- Titre : {$titre}\n"
            . (!empty($lieu)      ? "- Lieu : {$lieu}\n" : '')
            . (!empty($dateDebut) ? "- Date : {$dateDebut}\n" : '')
            . (!empty($prix)      ? "- Prix : {$prix} DT\n" : '')
            . (!empty($capacite)  ? "- Capacité : {$capacite} personnes\n" : '')
            . "Réponds uniquement avec la description. En français.";

        try {
            $response = $this->httpClient->request('POST', 'https://text.pollinations.ai/', [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => ['messages' => [['role' => 'user', 'content' => $prompt]], 'model' => 'openai', 'seed' => 42],
                'timeout' => 30,
            ]);
            $text = trim($response->getContent());
            if (empty($text)) return new JsonResponse(['error' => 'Réponse vide.'], 500);
            return new JsonResponse(['description' => $text]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/back/ai/generate-poster', name: 'back_ai_generate_poster', methods: ['POST'])]
    public function generatePoster(Request $request): JsonResponse
    {
        set_time_limit(180);

        $titre    = trim($request->request->get('titre', ''));
        $lieu     = trim($request->request->get('lieu', ''));
        $dateDebut= trim($request->request->get('date_debut', ''));
        $prix     = trim($request->request->get('prix', ''));

        if (empty($titre)) return new JsonResponse(['error' => 'Le titre est requis.'], 400);

        $prompt = "Agricultural event poster for \"{$titre}\""
            . (!empty($lieu)      ? ", at {$lieu}" : '')
            . (!empty($dateDebut) ? ", on {$dateDebut}" : '')
            . (!empty($prix)      ? ", price {$prix} DT" : '')
            . ". Green nature theme, professional flyer design, wheat fields, modern typography.";

        $imageUrl = "https://image.pollinations.ai/prompt/" . rawurlencode($prompt)
            . "?width=512&height=512&nologo=true&seed=" . rand(1, 9999);

        try {
            $response = $this->httpClient->request('GET', $imageUrl, [
                'timeout' => 150,
                'headers' => ['User-Agent' => 'Mozilla/5.0'],
            ]);
            if ($response->getStatusCode() !== 200) {
                return new JsonResponse(['error' => 'Pollinations HTTP ' . $response->getStatusCode()], 500);
            }
            $content = $response->getContent();
            $mime    = explode(';', $response->getHeaders(false)['content-type'][0] ?? 'image/jpeg')[0];
            $dataUri = 'data:' . $mime . ';base64,' . base64_encode($content);

            return new JsonResponse(['image_data' => $dataUri]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    #[Route('/back/ai/save-poster', name: 'back_ai_save_poster', methods: ['POST'])]
    public function savePoster(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $eventId  = (int) $request->request->get('event_id', 0);
        $imageData = trim($request->request->get('image_data', ''));

        if ($eventId <= 0 || empty($imageData)) {
            return new JsonResponse(['error' => 'Données manquantes.'], 400);
        }

        $ev = $em->getRepository(Evennementagricole::class)->find($eventId);
        if (!$ev) return new JsonResponse(['error' => 'Événement introuvable.'], 404);

        // Store base64 data URI directly in the image column
        $ev->setImage($imageData);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/whisper-stt', name: 'app_whisper_stt', methods: ['POST'])]
    public function whisperStt(Request $request): JsonResponse
    {
        // Placeholder - actual STT is handled client-side
        return new JsonResponse(['text' => '']);
    }

    #[Route('/back/ai/chat-sentiment/{id}', name: 'back_ai_chat_sentiment', methods: ['GET'])]
    public function chatSentiment(int $id, EntityManagerInterface $em): JsonResponse
    {
        $messages = $em->getRepository(\App\Entity\Messages::class)
            ->createQueryBuilder('m')
            ->where('m.event_id = :eid')
            ->setParameter('eid', $id)
            ->orderBy('m.timestamp', 'ASC')
            ->getQuery()->getResult();

        if (empty($messages)) {
            return new JsonResponse(['status' => 'empty', 'summary' => 'Aucun message dans ce chat.', 'bad' => [], 'good' => []]);
        }

        $textMessages = [];
        foreach ($messages as $msg) {
            $c = $msg->getContent();
            if (!str_starts_with($c, '[AUDIO')) $textMessages[] = $c;
        }

        if (empty($textMessages)) {
            return new JsonResponse(['status' => 'empty', 'summary' => 'Uniquement des messages vocaux.', 'bad' => [], 'good' => []]);
        }

        $chatText = implode("\n", array_map(fn($i, $m) => ($i+1).'. '.$m, array_keys($textMessages), $textMessages));

        $prompt = "Tu es un modérateur de chat pour une plateforme agricole. "
            . "Analyse ces messages et réponds UNIQUEMENT en JSON valide :\n"
            . "{\"sentiment\":\"positive\" ou \"negative\" ou \"mixed\","
            . "\"summary\":\"résumé en 2 phrases\","
            . "\"bad_messages\":[messages problématiques, vide si aucun],"
            . "\"good_messages\":[3 meilleurs messages positifs, vide si aucun]}\n\n"
            . "Messages:\n" . $chatText;

        try {
            $response = $this->httpClient->request('POST', 'https://text.pollinations.ai/', [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => ['messages' => [['role' => 'user', 'content' => $prompt]], 'model' => 'openai', 'seed' => 1],
                'timeout' => 30,
            ]);
            $raw = trim($response->getContent());
            if (preg_match('/\{.*\}/s', $raw, $m)) $raw = $m[0];
            $data = json_decode($raw, true);
            if (!$data || !isset($data['sentiment'])) throw new \Exception('Invalid JSON');

            return new JsonResponse([
                'status'  => $data['sentiment'],
                'summary' => $data['summary'] ?? '',
                'bad'     => $data['bad_messages'] ?? [],
                'good'    => $data['good_messages'] ?? [],
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
