<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrdonnanceIAController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey
    ) {}

    #[Route('/animal/ordonnance-ia', name: 'app_ordonnance_ia', methods: ['GET'])]
    public function index(AnimalRepository $animalRepo, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        return $this->render('front/suivi_animal/animal/ordonnance_ia.html.twig', [
            'animals' => $animalRepo->findAll(),
        ]);
    }

    // Auto-remplir poids depuis dernier suivi (AJAX)
    #[Route('/animal/ordonnance-ia/dernier-suivi/{id}', name: 'app_ordonnance_dernier_suivi', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function dernierSuivi(int $id, SuiviAnimalRepository $suiviRepo, AnimalRepository $animalRepo): JsonResponse
    {
        $animal = $animalRepo->find($id);
        if (!$animal) return new JsonResponse(null);

        $suivis = $suiviRepo->findBy(['animal' => $animal], ['dateSuivi' => 'DESC'], 1);
        if (empty($suivis)) return new JsonResponse(null);

        $s = $suivis[0];
        return new JsonResponse([
            'poids'    => $s->getPoids(),
            'remarque' => $s->getRemarque(),
            'etat'     => $s->getEtatSante(),
        ]);
    }

    #[Route('/animal/ordonnance-ia/generer', name: 'app_ordonnance_generer', methods: ['POST'])]
    public function generer(Request $request, AnimalRepository $animalRepo): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) return new JsonResponse(['error' => 'Non autorisé'], 401);

        $animalId   = $request->request->get('animalId');
        $poids      = (float) $request->request->get('poids', 0);
        $pathologie = $request->request->get('pathologie', '');
        $gravite    = $request->request->get('gravite', '');
        $symptomes  = $request->request->get('symptomes', '');
        $age        = $request->request->get('age', 'Adulte (1-7 ans)');
        $allergies  = $request->request->get('allergies', 'aucune connue');
        $gestante   = $request->request->get('gestante') === '1';
        $lactante   = $request->request->get('lactante') === '1';

        if (!$animalId || $poids <= 0 || !$pathologie) {
            return new JsonResponse(['error' => 'Données manquantes (animal, poids, pathologie requis).'], 400);
        }

        $animal = $animalRepo->find($animalId);
        if (!$animal) return new JsonResponse(['error' => 'Animal introuvable.'], 404);

        $prompt = $this->construirePrompt($animal, $poids, $pathologie, $gravite, $symptomes, $age, $allergies, $gestante, $lactante);

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.3-70b-versatile',
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.2,
                    'max_tokens'  => 2500,
                ],
            ]);

            $data    = $response->toArray();
            $reponse = $data['choices'][0]['message']['content'] ?? '';

            return new JsonResponse([
                'ordonnance' => $reponse,
                'animal'     => $animal->getCodeAnimal().' | '.$animal->getEspece().' '.$animal->getRace().' | '.$poids.' kg | '.$animal->getSexe(),
                'diagnostic' => $pathologie.' — '.$gravite,
                'date'       => (new \DateTime())->format('d/m/Y H:i'),
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur API : '.$e->getMessage()], 500);
        }
    }

    private function construirePrompt($animal, float $poids, string $pathologie, string $gravite, string $symptomes, string $age, string $allergies, bool $gestante, bool $lactante): string
    {
        $poidsInt = (int) $poids;

        return "Tu es un veterinaire expert en pharmacologie animale. Redige une ordonnance veterinaire COMPLETE et PRECISE en francais.\n\n"
            ."=== PATIENT ===\n"
            ."Code : {$animal->getCodeAnimal()}\n"
            ."Espece : {$animal->getEspece()} | Race : {$animal->getRace()} | Sexe : {$animal->getSexe()}\n"
            ."Poids : {$poids} kg | Age : {$age}\n"
            .($gestante ? "GESTANTE : OUI — eviter medicaments teratogenes\n" : "")
            .($lactante ? "LACTANTE : OUI — respecter temps d attente lait\n" : "")
            ."Allergies connues : {$allergies}\n\n"
            ."=== DIAGNOSTIC ===\n"
            ."Pathologie : {$pathologie}\n"
            ."Gravite : {$gravite}\n"
            .($symptomes ? "Symptomes : {$symptomes}\n" : "")
            ."\n=== ORDONNANCE DEMANDEE ===\n"
            ."Reponds STRICTEMENT avec ces sections :\n\n"
            ."## MEDICAMENTS\n"
            ."Pour chaque medicament, une ligne avec ce format EXACT (pipe | comme separateur) :\n"
            ."NOM | CLASSE | DOSE_mg_par_kg | DOSE_TOTALE_pour_{$poidsInt}kg | FREQUENCE | VOIE\n"
            ."Exemple : Amoxicilline | Antibiotique | 15 mg/kg | ".($poidsInt * 15)." mg | 2x/jour | Injectable IM\n"
            ."Liste 3 a 5 medicaments avec dosages PRECIS calcules pour {$poids} kg.\n\n"
            ."## POSOLOGIE DETAILLEE\n"
            ."Pour chaque medicament : instructions completes avec horaires.\n\n"
            ."## DUREE TRAITEMENT\n"
            ."Duree exacte en jours.\n\n"
            ."## CONTROLE VETERINAIRE\n"
            ."Quand revenir et signes d alarme.\n\n"
            ."## INSTRUCTIONS ELEVEUR\n"
            ."Instructions pratiques : conservation, manipulation, precautions.\n\n"
            ."## MISES EN GARDE\n"
            ."Contre-indications, effets secondaires, temps d attente viande/lait.";
    }
}
