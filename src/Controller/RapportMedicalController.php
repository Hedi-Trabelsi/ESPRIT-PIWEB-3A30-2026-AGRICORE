<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RapportMedicalController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey
    ) {}

    #[Route('/animal/rapport-medical', name: 'app_rapport_medical', methods: ['GET'])]
    public function index(AnimalRepository $animalRepo, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $animals = $animalRepo->findAll();

        return $this->render('front/suivi_animal/animal/rapport_medical.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/animal/rapport-medical/generer', name: 'app_rapport_medical_generer', methods: ['POST'])]
    public function generer(Request $request, AnimalRepository $animalRepo, SuiviAnimalRepository $suiviRepo): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return new JsonResponse(['error' => 'Non autorisé'], 401);
        }

        $animalId  = $request->request->get('animalId');
        $dateDebut = $request->request->get('dateDebut');
        $dateFin   = $request->request->get('dateFin');
        $style     = $request->request->get('style', 'complet');

        $animal = $animalRepo->find($animalId);
        if (!$animal) {
            return new JsonResponse(['error' => 'Animal introuvable'], 404);
        }

        // Charger les suivis sur la période
        $suivis = $suiviRepo->findByAnimalAndPeriode($animal, $dateDebut, $dateFin);

        if (empty($suivis)) {
            return new JsonResponse(['error' => 'Aucun suivi trouvé pour cette période.'], 404);
        }

        // Calculer les statistiques
        $temps = array_filter(array_map(fn($s) => $s->getTemperature(), $suivis));
        $poids = array_filter(array_map(fn($s) => $s->getPoids(), $suivis));
        $bpms  = array_filter(array_map(fn($s) => $s->getRythmeCardiaque(), $suivis));

        $stats = [
            'temp_min'  => $temps ? round(min($temps), 1) : 'N/A',
            'temp_max'  => $temps ? round(max($temps), 1) : 'N/A',
            'temp_moy'  => $temps ? round(array_sum($temps) / count($temps), 1) : 'N/A',
            'poids_min' => $poids ? round(min($poids), 1) : 'N/A',
            'poids_max' => $poids ? round(max($poids), 1) : 'N/A',
            'poids_moy' => $poids ? round(array_sum($poids) / count($poids), 1) : 'N/A',
            'bpm_min'   => $bpms ? min($bpms) : 'N/A',
            'bpm_max'   => $bpms ? max($bpms) : 'N/A',
            'bpm_moy'   => $bpms ? round(array_sum($bpms) / count($bpms)) : 'N/A',
        ];

        // Compter états de santé
        $etats = ['Bon' => 0, 'Moyen' => 0, 'Mauvais' => 0];
        $activites = ['Faible' => 0, 'Modéré' => 0, 'Élevé' => 0];
        $remarques = [];
        foreach ($suivis as $s) {
            if (isset($etats[$s->getEtatSante()])) $etats[$s->getEtatSante()]++;
            if (isset($activites[$s->getNiveauActivite()])) $activites[$s->getNiveauActivite()]++;
            if ($s->getRemarque()) $remarques[] = '- '.$s->getDateSuivi()->format('d/m/Y').': '.$s->getRemarque();
        }

        // Construire le prompt
        $styleDesc = match($style) {
            'synthese' => 'une synthèse courte et concise (max 300 mots)',
            'urgence'  => 'un rapport d\'urgence vétérinaire avec alertes et actions immédiates',
            default    => 'un rapport médical complet et détaillé',
        };

        $prompt = "Tu es un vétérinaire expert. Rédige {$styleDesc} en français pour l'animal suivant.

IDENTIFICATION DU PATIENT :
- Code animal : {$animal->getCodeAnimal()}
- Espèce : {$animal->getEspece()}
- Race : {$animal->getRace()}
- Sexe : {$animal->getSexe()}
- Date de naissance : ".($animal->getDateNaissance() ? $animal->getDateNaissance()->format('d/m/Y') : 'N/A')."

PÉRIODE D'ANALYSE : du {$dateDebut} au {$dateFin}
Nombre de suivis analysés : ".count($suivis)."

PARAMÈTRES VITAUX :
- Température : min {$stats['temp_min']}°C / max {$stats['temp_max']}°C / moy {$stats['temp_moy']}°C
- Poids : min {$stats['poids_min']}kg / max {$stats['poids_max']}kg / moy {$stats['poids_moy']}kg
- Rythme cardiaque : min {$stats['bpm_min']}bpm / max {$stats['bpm_max']}bpm / moy {$stats['bpm_moy']}bpm

ÉTATS DE SANTÉ OBSERVÉS :
- Bon : {$etats['Bon']} fois
- Moyen : {$etats['Moyen']} fois
- Mauvais : {$etats['Mauvais']} fois

NIVEAUX D'ACTIVITÉ :
- Faible : {$activites['Faible']} fois / Modéré : {$activites['Modéré']} fois / Élevé : {$activites['Élevé']} fois

REMARQUES CLINIQUES :
".(empty($remarques) ? 'Aucune remarque particulière.' : implode("\n", $remarques))."

Rédige le rapport avec les sections : Identification, Historique clinique, Bilan des paramètres vitaux, Analyse, Conclusion, Pronostic et Recommandations.";

        // Appel API Groq
        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.3-70b-versatile',
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens'  => 2000,
                ],
            ]);

            $data    = $response->toArray();
            $rapport = $data['choices'][0]['message']['content'] ?? 'Erreur de génération.';

            return new JsonResponse(['rapport' => $rapport]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur API : '.$e->getMessage()], 500);
        }
    }
}
