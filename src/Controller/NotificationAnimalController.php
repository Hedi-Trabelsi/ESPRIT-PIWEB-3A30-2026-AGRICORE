<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationAnimalController extends AbstractController
{
    // Normes vitales par espèce [tempMin, tempMax, rythmeMin, rythmeMax]
    private function getNormes(string $espece): array
    {
        return match (strtolower(trim($espece))) {
            'vache', 'bovin', 'bovins'    => [38.0, 39.5, 48,  84],
            'cheval', 'equin', 'poney'    => [37.5, 38.5, 28,  44],
            'mouton', 'brebis', 'ovin'    => [38.5, 39.5, 60, 120],
            'chèvre', 'chevre', 'caprin'  => [38.5, 39.5, 70,  80],
            'porc', 'truie', 'porcin'     => [38.0, 39.5, 60,  80],
            'poulet', 'poule', 'volaille' => [40.6, 41.7, 250, 300],
            'lapin'                       => [38.5, 39.5, 130, 325],
            default                       => [38.0, 39.5, 50,  100],
        };
    }

    #[Route('/animal/notifications', name: 'app_notifications_animal', methods: ['GET'])]
    public function index(AnimalRepository $animalRepo, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) return $this->redirectToRoute('front_login');

        return $this->render('front/suivi_animal/animal/notifications.html.twig', [
            'animals' => $animalRepo->findAll(),
        ]);
    }

    // Scanner toutes les anomalies (équivalent scannerAnomalies() Java)
    #[Route('/animal/notifications/scanner', name: 'app_notifications_scanner', methods: ['GET'])]
    public function scanner(AnimalRepository $animalRepo, SuiviAnimalRepository $suiviRepo): JsonResponse
    {
        $animals = $animalRepo->findAll();
        $alertes = [];

        foreach ($animals as $animal) {
            $suivis = $suiviRepo->findBy(['animal' => $animal], ['dateSuivi' => 'DESC'], 1);
            if (empty($suivis)) continue;

            $s      = $suivis[0];
            $normes = $this->getNormes($animal->getEspece());
            [$tempMin, $tempMax, $rythmeMin, $rythmeMax] = $normes;

            $temp   = $s->getTemperature();
            $rythme = $s->getRythmeCardiaque();
            $etat   = $s->getEtatSante();

            // ── Température ──
            if ($temp !== null) {
                if ($temp >= $tempMax + 1.5) {
                    $alertes[] = [
                        'animal'  => $animal->getCodeAnimal(),
                        'espece'  => $animal->getEspece(),
                        'race'    => $animal->getRace(),
                        'message' => "🔴 FIÈVRE SÉVÈRE : {$temp}°C (max normal {$tempMax}°C)",
                        'niveau'  => 'critique',
                        'valeur'  => $temp,
                        'type'    => 'temperature',
                    ];
                } elseif ($temp > $tempMax) {
                    $alertes[] = [
                        'animal'  => $animal->getCodeAnimal(),
                        'espece'  => $animal->getEspece(),
                        'race'    => $animal->getRace(),
                        'message' => "🟠 Fièvre légère : {$temp}°C",
                        'niveau'  => 'avertissement',
                        'valeur'  => $temp,
                        'type'    => 'temperature',
                    ];
                } elseif ($temp < $tempMin - 1.0) {
                    $alertes[] = [
                        'animal'  => $animal->getCodeAnimal(),
                        'espece'  => $animal->getEspece(),
                        'race'    => $animal->getRace(),
                        'message' => "🔴 HYPOTHERMIE : {$temp}°C (min normal {$tempMin}°C)",
                        'niveau'  => 'critique',
                        'valeur'  => $temp,
                        'type'    => 'temperature',
                    ];
                }
            }

            // ── Rythme cardiaque ──
            if ($rythme !== null) {
                if ($rythme > $rythmeMax + 20) {
                    $alertes[] = [
                        'animal'  => $animal->getCodeAnimal(),
                        'espece'  => $animal->getEspece(),
                        'race'    => $animal->getRace(),
                        'message' => "🔴 TACHYCARDIE : {$rythme} bpm (max {$rythmeMax} bpm)",
                        'niveau'  => 'critique',
                        'valeur'  => $rythme,
                        'type'    => 'rythme',
                    ];
                } elseif ($rythme < $rythmeMin - 10) {
                    $alertes[] = [
                        'animal'  => $animal->getCodeAnimal(),
                        'espece'  => $animal->getEspece(),
                        'race'    => $animal->getRace(),
                        'message' => "🔴 BRADYCARDIE : {$rythme} bpm (min {$rythmeMin} bpm)",
                        'niveau'  => 'critique',
                        'valeur'  => $rythme,
                        'type'    => 'rythme',
                    ];
                }
            }

            // ── État de santé ──
            if ($etat === 'Mauvais') {
                $alertes[] = [
                    'animal'  => $animal->getCodeAnimal(),
                    'espece'  => $animal->getEspece(),
                    'race'    => $animal->getRace(),
                    'message' => "🚨 ÉTAT MAUVAIS — Intervention urgente requise !",
                    'niveau'  => 'critique',
                    'valeur'  => $etat,
                    'type'    => 'etat',
                ];
            } elseif ($etat === 'Moyen') {
                $alertes[] = [
                    'animal'  => $animal->getCodeAnimal(),
                    'espece'  => $animal->getEspece(),
                    'race'    => $animal->getRace(),
                    'message' => "⚠️ État MOYEN — Surveillance renforcée recommandée",
                    'niveau'  => 'avertissement',
                    'valeur'  => $etat,
                    'type'    => 'etat',
                ];
            }
        }

        return new JsonResponse([
            'alertes'   => $alertes,
            'nbAlertes' => count($alertes),
            'timestamp' => (new \DateTime())->format('d/m/Y H:i:s'),
        ]);
    }
}
