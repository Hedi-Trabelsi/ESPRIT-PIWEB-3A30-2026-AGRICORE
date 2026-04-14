<?php

namespace App\Controller;

use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecommandationAlimentaireController extends AbstractController
{
    // ════════════════════════════════════════
    //  PROFILS NUTRITIONNELS PAR ESPÈCE
    //  (même logique que Java ProfilNutri)
    // ════════════════════════════════════════
    private function getProfilEspece(string $espece): array
    {
        return match (strtolower(trim($espece))) {
            'vache', 'bovin', 'bovins' => [
                'energie' => 24.0, 'proteine' => 12.0, 'fibre' => 35.0,
                'calcium' => 50.0, 'phosphore' => 30.0,
                'aliments' => ['Foin de graminées','Ensilage de maïs','Pulpe de betterave','Tourteau de soja','Orge concassé','Mélasse'],
                'interdits' => ['Avocat','Oignons','Pommes de terre crues','Champignons sauvages','Plantes toxiques (if, laurier)'],
            ],
            'cheval', 'jument', 'equin', 'poney' => [
                'energie' => 16.5, 'proteine' => 8.0, 'fibre' => 50.0,
                'calcium' => 30.0, 'phosphore' => 18.0,
                'aliments' => ['Foin de prairie','Avoine','Orge','Son de blé','Carottes','Pommes','Luzerne'],
                'interdits' => ['Pain en grande quantité','Sucre raffiné','Herbe fraîche excessive','Fèves','Chou','Tomates'],
            ],
            'mouton', 'brebis', 'ovin', 'agneau' => [
                'energie' => 8.5, 'proteine' => 10.0, 'fibre' => 40.0,
                'calcium' => 4.0, 'phosphore' => 3.0,
                'aliments' => ['Foin de bonne qualité','Pâturage naturel','Luzerne','Orge','Maïs','Tourteau de colza'],
                'interdits' => ['Cuivre en excès','Choux','Betteraves en excès','Plantes riches en oxalates','Rhubarbe'],
            ],
            'chèvre', 'chevre', 'caprin', 'bouc' => [
                'energie' => 9.0, 'proteine' => 11.0, 'fibre' => 38.0,
                'calcium' => 5.0, 'phosphore' => 3.5,
                'aliments' => ['Foin varié','Feuilles d\'arbres','Luzerne','Son de blé','Maïs','Légumes variés'],
                'interdits' => ['Ail en excès','Poireaux','Azalée','Laurier-rose','Rhododendron'],
            ],
            'porc', 'truie', 'porcin', 'cochon' => [
                'energie' => 12.0, 'proteine' => 15.0, 'fibre' => 10.0,
                'calcium' => 8.0, 'phosphore' => 6.0,
                'aliments' => ['Céréales (maïs, orge, blé)','Tourteau de soja','Pommes de terre cuites','Légumes cuits','Son de blé'],
                'interdits' => ['Viande crue','Sel en excès','Sucreries','Aliments moisis','Avocat'],
            ],
            'poulet', 'poule', 'volaille', 'coq' => [
                'energie' => 0.3, 'proteine' => 18.0, 'fibre' => 5.0,
                'calcium' => 3.5, 'phosphore' => 2.5,
                'aliments' => ['Maïs concassé','Tourteau de soja','Blé','Coquilles d\'huîtres','Vers de terre','Légumes verts hachés'],
                'interdits' => ['Avocat','Chocolat','Caféine','Sel','Oignons crus','Pommes de terre vertes'],
            ],
            'lapin' => [
                'energie' => 0.2, 'proteine' => 12.0, 'fibre' => 45.0,
                'calcium' => 1.0, 'phosphore' => 0.8,
                'aliments' => ['Foin de timothy','Granulés spécifiques lapins','Légumes verts frais','Carottes','Herbes fraîches'],
                'interdits' => ['Laitue iceberg','Oignons','Rhubarbe','Pommes de terre','Maïs','Sucre','Chocolat'],
            ],
            default => [
                'energie' => 10.0, 'proteine' => 12.0, 'fibre' => 25.0,
                'calcium' => 5.0, 'phosphore' => 3.0,
                'aliments' => ['Fourrage adapté','Compléments minéraux','Eau fraîche en permanence'],
                'interdits' => ['Aliments moisis','Plantes non identifiées'],
            ],
        };
    }

    #[Route('/animal/recommandation-alimentaire', name: 'app_recommandation_alimentaire', methods: ['GET'])]
    public function index(AnimalRepository $animalRepo, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) return $this->redirectToRoute('front_login');

        return $this->render('front/suivi_animal/animal/recommandation_alimentaire.html.twig', [
            'animals' => $animalRepo->findAll(),
        ]);
    }

    #[Route('/animal/recommandation-alimentaire/dernier-suivi/{id}', name: 'app_reco_dernier_suivi', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function dernierSuivi(int $id, SuiviAnimalRepository $suiviRepo, AnimalRepository $animalRepo): JsonResponse
    {
        $animal = $animalRepo->find($id);
        if (!$animal) return new JsonResponse(null);
        $suivis = $suiviRepo->findBy(['animal' => $animal], ['dateSuivi' => 'DESC'], 1);
        if (empty($suivis)) return new JsonResponse(null);
        $s = $suivis[0];
        return new JsonResponse([
            'poids'    => $s->getPoids(),
            'etat'     => $s->getEtatSante(),
            'activite' => $s->getNiveauActivite(),
        ]);
    }

    #[Route('/animal/recommandation-alimentaire/generer', name: 'app_reco_generer', methods: ['POST'])]
    public function generer(Request $request, AnimalRepository $animalRepo): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) return new JsonResponse(['error' => 'Non autorisé'], 401);

        $animalId = $request->request->get('animalId');
        $poids    = (float) $request->request->get('poids', 0);
        $age      = (int)   $request->request->get('age', 1);
        $etat     = $request->request->get('etat', 'Bon');
        $niveau   = $request->request->get('niveau', 'Moyen');
        $saison   = $request->request->get('saison', 'Printemps');
        $objectif = $request->request->get('objectif', 'Maintien du poids');

        if (!$animalId || $poids <= 0) return new JsonResponse(['error' => 'Animal et poids requis.'], 400);

        $animal = $animalRepo->find($animalId);
        if (!$animal) return new JsonResponse(['error' => 'Animal introuvable.'], 404);

        $espece = $animal->getEspece();
        $race   = $animal->getRace();
        $profil = $this->getProfilEspece($espece);

        // ════════════════════════════════════════
        //  MOTEUR DE RECOMMANDATION (même logique Java)
        // ════════════════════════════════════════
        $aliments    = $profil['aliments'];
        $supplements = [];
        $interdits   = $profil['interdits'];
        $plan        = [];
        $scoreNutri  = 70;

        // ── État de santé ──
        match ($etat) {
            'Mauvais', 'Malade' => (function() use (&$supplements, &$plan, &$scoreNutri) {
                $supplements[] = '💊 Électrolytes — Réhydratation';
                $supplements[] = '🌿 Probiotiques — Restaurer la flore intestinale';
                $supplements[] = '🍋 Vitamine C — Renforcer l\'immunité';
                $plan[] = '🔴 Réduire les rations de 30% pendant la maladie';
                $plan[] = '💧 Eau fraîche en permanence (augmenter de 50%)';
                $plan[] = '📅 Repas fractionnés : 4-5 petits repas par jour';
                $scoreNutri -= 15;
            })(),
            'Moyen', 'Convalescent' => (function() use (&$supplements, &$plan, &$scoreNutri) {
                $supplements[] = '🌿 Probiotiques — Récupération digestive';
                $supplements[] = '💊 Multivitamines — Récupération générale';
                $plan[] = '📈 Augmenter progressivement les rations (+10%/semaine)';
                $scoreNutri += 5;
            })(),
            default => null,
        };

        // ── Objectif ──
        match ($objectif) {
            'Prise de poids' => (function() use (&$supplements, &$plan, &$scoreNutri) {
                $supplements[] = '🌽 Énergie dense — Maïs + orge en supplément';
                $supplements[] = '💪 Acides aminés essentiels — Lysine + méthionine';
                $plan[] = '📈 Augmenter ration de 15-20% progressivement';
                $plan[] = '🕐 3 repas par jour minimum';
                $scoreNutri += 10;
            })(),
            'Perte de poids' => (function() use (&$plan, &$interdits, &$scoreNutri) {
                $plan[] = '📉 Réduire les céréales de 30%';
                $plan[] = '🌿 Augmenter le fourrage grossier (foin, paille)';
                $plan[] = '🏃 Augmenter l\'activité physique progressivement';
                $interdits[] = 'Aliments riches en sucres (mélasse, betterave en excès)';
                $scoreNutri -= 5;
            })(),
            'Production laitière' => (function() use (&$supplements, &$plan, &$scoreNutri) {
                $supplements[] = '⚡ Énergie — Maïs + son de blé + mélasse';
                $supplements[] = '🥩 Protéines — Tourteau de soja ou colza';
                $supplements[] = '🦴 Minéraux lait — Calcium + Phosphore + Magnésium';
                $plan[] = '💧 Eau à volonté — 1L d\'eau produit 1L de lait';
                $scoreNutri += 15;
            })(),
            'Croissance' => (function() use (&$supplements, &$plan) {
                $supplements[] = '🥩 Protéines élevées — 18-20% de la ration';
                $supplements[] = '🦴 Calcium + Phosphore + Vitamine D — Ossification';
                $plan[] = '🍽️ 4 repas par jour pour les jeunes animaux';
            })(),
            default => null,
        };

        // ── Âge ──
        if ($age <= 1) {
            $supplements[] = '🍼 Colostrum ou lait maternel si < 3 mois';
            $plan[] = '👶 Jeune animal : augmenter fréquence des repas';
        } elseif ($age >= 8) {
            $supplements[] = '🦴 Calcium + Vitamine D — Prévenir l\'arthrose';
            $plan[] = '👴 Animal âgé : aliments plus digestibles';
            $plan[] = '🦷 Vérifier l\'état dentaire régulièrement';
        }

        // ── Saison ──
        match ($saison) {
            'Été' => (function() use (&$supplements, &$plan) {
                $supplements[] = '💧 Électrolytes — Compenser la sudation';
                $plan[] = '☀️ Été : repas le matin tôt et le soir tard';
                $plan[] = '💧 Doubler les points d\'eau';
            })(),
            'Hiver' => (function() use (&$supplements, &$plan) {
                $supplements[] = '⚡ Énergie supplémentaire — +15% de céréales';
                $supplements[] = '☀️ Vitamine D — Manque d\'ensoleillement';
                $plan[] = '❄️ Hiver : augmenter les rations de 10-20%';
            })(),
            'Printemps' => (function() use (&$supplements, &$plan) {
                $supplements[] = '🌿 Magnésium — Prévenir la tétanie d\'herbage';
                $plan[] = '🌱 Introduction progressive de l\'herbe fraîche';
                $plan[] = '⚠️ Limiter herbe à 2h/jour les premières semaines';
            })(),
            'Automne' => (function() use (&$supplements, &$plan) {
                $supplements[] = '🛡️ Vitamines A+D+E — Préparer l\'hiver';
                $plan[] = '🍂 Constituer les réserves corporelles pour l\'hiver';
            })(),
            default => null,
        };

        // ── Niveau d'activité ──
        if ($niveau === 'Élevé' || $niveau === 'Eleve') {
            $supplements[] = '⚡ Glucides rapides — Maïs, avoine pour l\'énergie';
            $plan[] = '🏃 Activité élevée : +25% d\'apport énergétique';
        } elseif ($niveau === 'Faible') {
            $plan[] = '😴 Activité faible : réduire les glucides de 20%';
            $interdits[] = 'Céréales en excès (risque d\'obésité)';
        }

        // ── Score final ──
        $scoreNutri = max(0, min(100, $scoreNutri));
        if ($scoreNutri >= 80)      { $statut = '✅ EXCELLENT';    $couleur = '#2e7d32'; }
        elseif ($scoreNutri >= 60)  { $statut = '⚠️ SATISFAISANT'; $couleur = '#f57c00'; }
        elseif ($scoreNutri >= 40)  { $statut = '🔴 INSUFFISANT';  $couleur = '#e65100'; }
        else                        { $statut = '🚨 CRITIQUE';     $couleur = '#c62828'; }

        // ── Besoins journaliers ──
        $facteur = $niveau === 'Élevé' ? 1.25 : ($niveau === 'Faible' ? 0.85 : 1.0);
        $besoins = [
            'energie'   => round($profil['energie'] * $facteur, 1),
            'proteine'  => round($profil['proteine'] * $poids / 1000, 2),
            'fibre'     => $profil['fibre'],
            'calcium'   => $profil['calcium'],
            'phosphore' => $profil['phosphore'],
            'eau_min'   => round($poids * 0.1),
            'eau_max'   => round($poids * 0.15),
        ];

        return new JsonResponse([
            'animal'      => $animal->getCodeAnimal().' — '.$espece.' / '.$race.' | '.$poids.'kg | '.$age.' ans | '.$etat,
            'scoreNutri'  => $scoreNutri,
            'statut'      => $statut,
            'couleur'     => $couleur,
            'aliments'    => $aliments,
            'supplements' => $supplements,
            'interdits'   => $interdits,
            'plan'        => $plan,
            'besoins'     => $besoins,
        ]);
    }
}
