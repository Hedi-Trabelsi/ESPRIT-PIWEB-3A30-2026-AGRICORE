<?php

namespace App\Controller;

use App\Entity\SuiviAnimal;
use App\Form\SuiviAnimalType;
use App\Repository\SuiviAnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/suivi/animal')]
final class SuiviAnimalController extends AbstractController
{
    #[Route(name: 'app_suivi_animal_index', methods: ['GET'])]
    public function index(Request $request, SuiviAnimalRepository $suiviAnimalRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $q      = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'dateSuivi');
        $order  = $request->query->get('order', 'DESC');

        $suivis = $suiviAnimalRepository->search($q, $sortBy, $order, $sessionUser->getId());

        if ($request->isXmlHttpRequest()) {
            return $this->render('front/suivi_animal/suivi_animal/_cards.html.twig', [
                'suivi_animals' => $suivis,
            ]);
        }

        return $this->render('front/suivi_animal/suivi_animal/index.html.twig', [
            'suivi_animals' => $suivis,
            'q'             => $q,
            'sortBy'        => $sortBy,
            'order'         => $order,
        ]);
    }

    #[Route('/recherche', name: 'app_suivi_animal_search', methods: ['GET'])]
    public function searchStatic(Request $request, SuiviAnimalRepository $suiviAnimalRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $etatSante      = $request->query->get('etatSante', '');
        $niveauActivite = $request->query->get('niveauActivite', '');
        $tempMin        = $request->query->get('tempMin') !== '' ? (float)$request->query->get('tempMin') : null;
        $tempMax        = $request->query->get('tempMax') !== '' ? (float)$request->query->get('tempMax') : null;
        $sortBy         = $request->query->get('sortBy', 'dateSuivi');
        $order          = $request->query->get('order', 'DESC');

        $suivis = $suiviAnimalRepository->searchStatic($etatSante, $niveauActivite, $tempMin, $tempMax, $sortBy, $order, $sessionUser->getId());

        return $this->render('front/suivi_animal/suivi_animal/search.html.twig', [
            'suivi_animals'  => $suivis,
            'etatSante'      => $etatSante,
            'niveauActivite' => $niveauActivite,
            'tempMin'        => $tempMin,
            'tempMax'        => $tempMax,
            'sortBy'         => $sortBy,
            'order'          => $order,
        ]);
    }

    #[Route('/export-pdf', name: 'app_suivi_animal_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, SuiviAnimalRepository $suiviAnimalRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $q      = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'dateSuivi');
        $order  = $request->query->get('order', 'DESC');

        $suivis = $suiviAnimalRepository->search($q, $sortBy, $order, $sessionUser->getId());

        $html = $this->renderView('front/suivi_animal/suivi_animal/pdf.html.twig', [
            'suivi_animals' => $suivis,
            'date'          => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="suivis_'.date('Ymd').'.pdf"',
            ]
        );
    }

    #[Route('/new', name: 'app_suivi_animal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $suiviAnimal = new SuiviAnimal();
        $suiviAnimal->setDateSuivi(new \DateTime());

        // Présélectionner l'animal si animalId est dans l'URL
        $animalId    = $request->query->get('animalId');
        $fixedAnimal = null;
        if ($animalId) {
            $fixedAnimal = $entityManager->getRepository(\App\Entity\Animal::class)->find($animalId);
            if ($fixedAnimal) {
                $suiviAnimal->setAnimal($fixedAnimal);
            }
        }

        $form = $this->createForm(SuiviAnimalType::class, $suiviAnimal, [
            'idAgriculteur' => $sessionUser->getId(),
            'fixedAnimal'   => $fixedAnimal,
        ]);
        $form->handleRequest($request);

        // Si animal fixé, le réassigner après handleRequest (champ absent du form = remis à null)
        if ($fixedAnimal !== null) {
            $suiviAnimal->setAnimal($fixedAnimal);
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($suiviAnimal);
            $entityManager->flush();

            // ── Analyser les données et stocker alertes en session ──
            $alertes = $this->analyserSuivi($suiviAnimal);
            if (!empty($alertes)) {
                $request->getSession()->set('suivi_alertes', $alertes);
            }

            if ($animalId) {
                return $this->redirectToRoute('app_animal_show', ['idAnimal' => $animalId], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_suivi_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front/suivi_animal/suivi_animal/new.html.twig', [
            'suivi_animal' => $suiviAnimal,
            'form'         => $form,
            'animalCode'   => $fixedAnimal ? $fixedAnimal->getCodeAnimal() : null,
            'animalId'     => $animalId,
        ]);
    }

    #[Route('/{idSuivi}', name: 'app_suivi_animal_show', methods: ['GET'])]
    public function show(
        #[MapEntity(mapping: ['idSuivi' => 'idSuivi'])] SuiviAnimal $suiviAnimal
    ): Response {
        return $this->render('front/suivi_animal/suivi_animal/show.html.twig', [
            'suivi_animal' => $suiviAnimal,
        ]);
    }

    #[Route('/{idSuivi}/edit', name: 'app_suivi_animal_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['idSuivi' => 'idSuivi'])] SuiviAnimal $suiviAnimal,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer l'idAnimal directement en SQL pour éviter tout proxy Doctrine
        $conn = $entityManager->getConnection();
        $idAnimal = $conn->fetchOne('SELECT idAnimal FROM suivi_animal WHERE idSuivi = ?', [$suiviAnimal->getIdSuivi()]);

        $animal = null;
        $animalCode = null;
        if ($idAnimal) {
            $animal = $entityManager->getRepository(\App\Entity\Animal::class)->find((int)$idAnimal);
            if ($animal) {
                $suiviAnimal->setAnimal($animal);
                $animalCode = $animal->getCodeAnimal();
            }
        }

        $form = $this->createForm(SuiviAnimalType::class, $suiviAnimal, [
            'fixedAnimal' => $animal,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // ── Analyser et notifier si anomalie ──
            $alertes = $this->analyserSuivi($suiviAnimal);
            if (!empty($alertes)) {
                $request->getSession()->set('suivi_alertes', $alertes);
            }

            if ($idAnimal) {
                return $this->redirectToRoute('app_animal_show', ['idAnimal' => (int)$idAnimal], Response::HTTP_SEE_OTHER);
            }
            return $this->redirectToRoute('app_suivi_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front/suivi_animal/suivi_animal/edit.html.twig', [
            'suivi_animal' => $suiviAnimal,
            'form'         => $form,
            'animalCode'   => $animalCode,
            'animalId'     => $idAnimal,
        ]);
    }

    #[Route('/{idSuivi}', name: 'app_suivi_animal_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['idSuivi' => 'idSuivi'])] SuiviAnimal $suiviAnimal,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer l'idAnimal avant suppression pour rediriger vers la fiche
        $conn = $entityManager->getConnection();
        $idAnimal = $conn->fetchOne('SELECT idAnimal FROM suivi_animal WHERE idSuivi = ?', [$suiviAnimal->getIdSuivi()]);

        if ($this->isCsrfTokenValid('delete'.$suiviAnimal->getIdSuivi(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($suiviAnimal);
            $entityManager->flush();
        }

        if ($idAnimal) {
            return $this->redirectToRoute('app_animal_show', ['idAnimal' => (int)$idAnimal], Response::HTTP_SEE_OTHER);
        }
        return $this->redirectToRoute('app_suivi_animal_index', [], Response::HTTP_SEE_OTHER);
    }

    private function analyserSuivi(\App\Entity\SuiviAnimal $suivi): array
    {
        $alertes = [];
        $animal  = $suivi->getAnimal();
        $code    = $animal ? $animal->getCodeAnimal() : 'Animal';
        $espece  = $animal ? strtolower($animal->getEspece()) : '';

        $normes = match(true) {
            in_array($espece, ['vache','bovin','bovins'])    => [38.0, 39.5, 48,  84],
            in_array($espece, ['cheval','equin','poney'])    => [37.5, 38.5, 28,  44],
            in_array($espece, ['mouton','brebis','ovin'])    => [38.5, 39.5, 60, 120],
            in_array($espece, ['chèvre','chevre','caprin'])  => [38.5, 39.5, 70,  80],
            in_array($espece, ['porc','truie','porcin'])     => [38.0, 39.5, 60,  80],
            in_array($espece, ['poulet','poule','volaille']) => [40.6, 41.7, 250, 300],
            $espece === 'lapin'                              => [38.5, 39.5, 130, 325],
            default                                          => [38.0, 39.5, 50,  100],
        };
        [$tempMin, $tempMax, $rythmeMin, $rythmeMax] = $normes;

        $temp = $suivi->getTemperature();
        if ($temp !== null) {
            if ($temp >= $tempMax + 1.5)
                $alertes[] = ['titre' => "🔴 FIÈVRE SÉVÈRE — {$code}", 'message' => "Température critique : {$temp}°C (max {$tempMax}°C)", 'niveau' => 'critique'];
            elseif ($temp > $tempMax)
                $alertes[] = ['titre' => "🟠 Fièvre — {$code}", 'message' => "Température élevée : {$temp}°C", 'niveau' => 'avertissement'];
            elseif ($temp < $tempMin - 1.0)
                $alertes[] = ['titre' => "🔴 HYPOTHERMIE — {$code}", 'message' => "Température basse : {$temp}°C (min {$tempMin}°C)", 'niveau' => 'critique'];
        }

        $rythme = $suivi->getRythmeCardiaque();
        if ($rythme !== null) {
            if ($rythme > $rythmeMax + 20)
                $alertes[] = ['titre' => "🔴 TACHYCARDIE — {$code}", 'message' => "Rythme élevé : {$rythme} bpm (max {$rythmeMax})", 'niveau' => 'critique'];
            elseif ($rythme < $rythmeMin - 10)
                $alertes[] = ['titre' => "🔴 BRADYCARDIE — {$code}", 'message' => "Rythme bas : {$rythme} bpm (min {$rythmeMin})", 'niveau' => 'critique'];
        }

        $etat = $suivi->getEtatSante();
        if ($etat === 'Mauvais')
            $alertes[] = ['titre' => "🚨 ÉTAT MAUVAIS — {$code}", 'message' => "Intervention vétérinaire urgente requise !", 'niveau' => 'critique'];
        elseif ($etat === 'Moyen')
            $alertes[] = ['titre' => "⚠️ État moyen — {$code}", 'message' => "Surveillance renforcée recommandée", 'niveau' => 'avertissement'];

        return $alertes;
    }
}
