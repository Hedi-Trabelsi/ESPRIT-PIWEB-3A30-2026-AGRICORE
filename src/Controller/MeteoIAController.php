<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Cmfcmf\OpenWeatherMap;
use Cmfcmf\OpenWeatherMap\Exception as OWMException;
use Http\Factory\Guzzle\RequestFactory;
use Http\Factory\Guzzle\UriFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MeteoIAController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $groqApiKey,
        private string $owmApiKey
    ) {}

    #[Route('/animal/meteo-ia', name: 'app_meteo_ia', methods: ['GET'])]
    public function index(AnimalRepository $animalRepo, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        return $this->render('front/suivi_animal/animal/meteo_ia.html.twig', [
            'animals' => $animalRepo->findAll(),
        ]);
    }

    #[Route('/animal/meteo-ia/analyser', name: 'app_meteo_analyser', methods: ['POST'])]
    public function analyser(Request $request, AnimalRepository $animalRepo, SuiviAnimalRepository $suiviRepo): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) return new JsonResponse(['error' => 'Non autorisé'], 401);

        $ville = trim((string) $request->request->get('ville', ''));
        if (!$ville) return new JsonResponse(['error' => 'Veuillez entrer une ville.'], 400);

        // ── 1. Récupérer la météo via OpenWeatherMap ──
        try {
            $meteoUrl = "https://api.openweathermap.org/data/2.5/weather?q={$ville}&appid={$this->owmApiKey}&units=metric&lang=fr";
            $meteoResp = $this->httpClient->request('GET', $meteoUrl);
            $meteo = $meteoResp->toArray();

            if (isset($meteo['cod']) && $meteo['cod'] != 200) {
                return new JsonResponse(['error' => 'Ville introuvable : '.$ville], 404);
            }

            // Prévisions 5 jours
            $forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?q={$ville}&appid={$this->owmApiKey}&units=metric&lang=fr&cnt=5";
            $forecastResp = $this->httpClient->request('GET', $forecastUrl);
            $forecast = $forecastResp->toArray();

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur météo : '.$e->getMessage()], 500);
        }

        // ── 2. Extraire données météo ──
        $main = is_array($meteo['main'] ?? null) ? $meteo['main'] : [];
        $wind = is_array($meteo['wind'] ?? null) ? $meteo['wind'] : [];
        $weatherList = is_array($meteo['weather'] ?? null) ? $meteo['weather'] : [];
        $weather0 = isset($weatherList[0]) && is_array($weatherList[0]) ? $weatherList[0] : [];
        $rainArr = is_array($meteo['rain'] ?? null) ? $meteo['rain'] : [];
        $temp        = is_numeric($main['temp'] ?? null) ? (float) $main['temp'] : 0;
        $tempMin     = is_numeric($main['temp_min'] ?? null) ? (float) $main['temp_min'] : 0;
        $tempMax     = is_numeric($main['temp_max'] ?? null) ? (float) $main['temp_max'] : 0;
        $humidity    = is_numeric($main['humidity'] ?? null) ? (int) $main['humidity'] : 0;
        $windSpeed   = is_numeric($wind['speed'] ?? null) ? (float) $wind['speed'] : 0;
        $description = is_string($weather0['description'] ?? null) ? $weather0['description'] : '';
        $icon        = is_string($weather0['icon'] ?? null) ? $weather0['icon'] : '01d';
        $pressure    = is_numeric($main['pressure'] ?? null) ? (int) $main['pressure'] : 0;
        $feelsLike   = is_numeric($main['feels_like'] ?? null) ? (float) $main['feels_like'] : 0;
        $rain        = is_numeric($rainArr['1h'] ?? null) ? (float) $rainArr['1h'] : 0;

        // ── 3. Charger animaux + derniers suivis ──
        $animals = $animalRepo->findAll();
        $animalData = [];
        foreach ($animals as $animal) {
            $suivis = $suiviRepo->findBy(['animal' => $animal], ['dateSuivi' => 'DESC'], 1);
            $dernierSuivi = $suivis[0] ?? null;
            $animalData[] = [
                'code'    => $animal->getCodeAnimal(),
                'espece'  => $animal->getEspece(),
                'race'    => $animal->getRace(),
                'sexe'    => $animal->getSexe(),
                'etat'    => $dernierSuivi ? $dernierSuivi->getEtatSante() : 'Inconnu',
                'temp'    => $dernierSuivi ? $dernierSuivi->getTemperature() : null,
                'poids'   => $dernierSuivi ? $dernierSuivi->getPoids() : null,
                'activite'=> $dernierSuivi ? $dernierSuivi->getNiveauActivite() : null,
            ];
        }

        // ── 4. Construire prompt IA ──
        $animalsList = '';
        foreach ($animalData as $a) {
            $animalsList .= "- {$a['code']} ({$a['espece']} {$a['race']}, {$a['sexe']}) : état={$a['etat']}, temp_corporelle={$a['temp']}°C, activité={$a['activite']}\n";
        }

        $prompt = "Tu es un expert vétérinaire et agronome spécialisé en bien-être animal et météorologie agricole.\n\n"
            ."=== MÉTÉO ACTUELLE — {$ville} ===\n"
            ."Température : {$temp}°C (ressenti {$feelsLike}°C, min {$tempMin}°C, max {$tempMax}°C)\n"
            ."Conditions : {$description}\n "
            ."Humidité : {$humidity}%\n"
            ."Vent : {$windSpeed} m/s\n"
            ."Pression : {$pressure} hPa\n"
            .($rain > 0 ? "Pluie : {$rain} mm/h\n" : "")
            ."\n=== CHEPTEL ({$this->count($animalData)} animaux) ===\n"
            .$animalsList
            ."\n=== ANALYSE DEMANDÉE ===\n"
            ."Réponds STRICTEMENT avec ces sections :\n\n"
            ."## SCORE DE RISQUE\n"
            ."Donne un score de risque global : FAIBLE / MODÉRÉ / ÉLEVÉ / CRITIQUE\n"
            ."Explique en 2 phrases pourquoi.\n\n"
            ."## IMPACT PAR ESPÈCE\n"
            ."Pour chaque espèce présente dans le cheptel, analyse l'impact de cette météo.\n\n"
            ."## ALERTES\n"
            ."Liste les alertes urgentes si la météo présente des risques (chaleur, froid, humidité, vent).\n"
            ."Si aucun risque, écris : Aucune alerte — conditions favorables.\n\n"
            ."## CONSEILS PRATIQUES\n"
            ."5 conseils concrets et actionnables pour l'agriculteur aujourd'hui.\n\n"
            ."## PRÉVISIONS\n"
            ."Basé sur les conditions actuelles, donne des recommandations pour les prochains jours.\n\n"
            ."## CONCLUSION\n"
            ."Résumé en 3 phrases : est-ce que les conditions météo sont favorables ou non pour ce cheptel ?";

        // ── 5. Appel Groq IA ──
        try {
            $groqResp = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.3-70b-versatile',
                    'messages'    => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.4,
                    'max_tokens'  => 2000,
                ],
            ]);

            $groqData = $groqResp->toArray();
            $choices = $groqData['choices'] ?? [];
            $analyse = '';
            if (is_array($choices) && isset($choices[0]) && is_array($choices[0])) {
                $msg = $choices[0]['message'] ?? null;
                if (is_array($msg) && isset($msg['content']) && is_string($msg['content'])) {
                    $analyse = $msg['content'];
                }
            }

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur IA : '.$e->getMessage()], 500);
        }

        // ── 6. Prévisions formatées ──
        $previsions = [];
        $list = $forecast['list'] ?? null;
        if (is_array($list)) {
            foreach (array_slice($list, 0, 5) as $f) {
                if (!is_array($f)) continue;
                $fMain = is_array($f['main'] ?? null) ? $f['main'] : [];
                $fWeather = is_array($f['weather'] ?? null) ? $f['weather'] : [];
                $fWeather0 = isset($fWeather[0]) && is_array($fWeather[0]) ? $fWeather[0] : [];
                $previsions[] = [
                    'heure' => date('H:i', is_numeric($f['dt'] ?? null) ? (int) $f['dt'] : 0),
                    'temp'  => round(is_numeric($fMain['temp'] ?? null) ? (float) $fMain['temp'] : 0),
                    'desc'  => is_string($fWeather0['description'] ?? null) ? $fWeather0['description'] : '',
                    'icon'  => is_string($fWeather0['icon'] ?? null) ? $fWeather0['icon'] : '01d',
                    'hum'   => is_numeric($fMain['humidity'] ?? null) ? (int) $fMain['humidity'] : 0,
                ];
            }
        }

        $sys = is_array($meteo['sys'] ?? null) ? $meteo['sys'] : [];
        return new JsonResponse([
            'meteo' => [
                'ville'       => is_string($meteo['name'] ?? null) ? $meteo['name'] : $ville,
                'pays'        => is_string($sys['country'] ?? null) ? $sys['country'] : '',
                'temp'        => round($temp),
                'tempMin'     => round($tempMin),
                'tempMax'     => round($tempMax),
                'feelsLike'   => round($feelsLike),
                'humidity'    => $humidity,
                'windSpeed'   => $windSpeed,
                'description' => ucfirst($description),
                'icon'        => $icon,
                'pressure'    => $pressure,
                'rain'        => $rain,
            ],
            'previsions' => $previsions,
            'animaux'    => count($animalData),
            'analyse'    => $analyse,
        ]);
    }

    /**
     * @param array<mixed> $arr
     */
    private function count(array $arr): int { return count($arr); }
}
