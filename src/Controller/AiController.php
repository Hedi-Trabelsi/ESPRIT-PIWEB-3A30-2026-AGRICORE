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
    public function generatePoster(Request $request, EntityManagerInterface $em): JsonResponse
    {
        set_time_limit(180);

        $titre    = trim($request->request->get('titre', ''));
        $lieu     = trim($request->request->get('lieu', ''));
        $dateDebut= trim($request->request->get('date_debut', ''));
        $prix     = trim($request->request->get('prix', ''));
        $eventId  = (int) $request->request->get('event_id', 0);

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

            // Save as temp file so it can be linked after event creation
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
            $tempKey  = 'tmp_' . uniqid() . '.jpg';
            file_put_contents($uploadDir . '/' . $tempKey, $content);

            return new JsonResponse([
                'image_data' => $dataUri,
                'temp_key'   => $tempKey,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }
    #[Route('/back/ai/save-poster', name: 'back_ai_save_poster', methods: ['POST'])]
    public function savePoster(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $eventId  = (int) $request->request->get('event_id', 0);
        $imageB64 = $request->request->get('image_data', '');

        if ($eventId <= 0 || empty($imageB64)) {
            return new JsonResponse(['error' => 'Données manquantes.'], 400);
        }

        $ev = $em->getRepository(Evennementagricole::class)->find($eventId);
        if (!$ev) return new JsonResponse(['error' => 'Événement introuvable.'], 404);

        // Decode base64 data URI
        if (preg_match('/^data:image\/\w+;base64,/', $imageB64)) {
            $content = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $imageB64));
        } else {
            return new JsonResponse(['error' => 'Format image invalide.'], 400);
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/events';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
        $filename = 'event_' . $eventId . '_' . time() . '.jpg';
        file_put_contents($uploadDir . '/' . $filename, $content);
        $ev->setImage('uploads/events/' . $filename);
        $em->flush();

        return new JsonResponse(['success' => true, 'path' => 'uploads/events/' . $filename]);
    }
}
