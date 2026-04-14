<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Form\AnimalType;
use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

#[Route('/animal')]
final class AnimalController extends AbstractController
{
    #[Route(name: 'app_animal_index', methods: ['GET'])]
    public function index(Request $request, AnimalRepository $animalRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $q      = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'codeAnimal');
        $order  = $request->query->get('order', 'ASC');

        $animals = $animalRepository->search($q, $sortBy, $order, null);

        if ($request->isXmlHttpRequest()) {
            return $this->render('front/suivi_animal/animal/_cards.html.twig', [
                'animals'    => $animals,
                'sessionUser' => $sessionUser,
            ]);
        }

        return $this->render('front/suivi_animal/animal/index.html.twig', [
            'animals' => $animals,
            'q'       => $q,
            'sortBy'  => $sortBy,
            'order'   => $order,
        ]);
    }

    #[Route('/recherche', name: 'app_animal_search', methods: ['GET'])]
    public function searchStatic(Request $request, AnimalRepository $animalRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $codeAnimal = $request->query->get('codeAnimal', '');
        $espece     = $request->query->get('espece', '');
        $race       = $request->query->get('race', '');
        $sexe       = $request->query->get('sexe', '');
        $sortBy     = $request->query->get('sortBy', 'codeAnimal');
        $order      = $request->query->get('order', 'ASC');

        $animals = $animalRepository->searchStatic($codeAnimal, $espece, $race, $sexe, $sortBy, $order, null);

        return $this->render('front/suivi_animal/animal/search.html.twig', [
            'animals'    => $animals,
            'codeAnimal' => $codeAnimal,
            'espece'     => $espece,
            'race'       => $race,
            'sexe'       => $sexe,
            'sortBy'     => $sortBy,
            'order'      => $order,
        ]);
    }

    #[Route('/export-pdf', name: 'app_animal_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, AnimalRepository $animalRepository): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $q      = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'codeAnimal');
        $order  = $request->query->get('order', 'ASC');

        $animals = $animalRepository->search($q, $sortBy, $order, null);

        $html = $this->renderView('front/suivi_animal/animal/pdf.html.twig', [
            'animals' => $animals,
            'date'    => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'inline; filename="animaux_'.date('Ymd').'.pdf"',
            ]
        );
    }

    #[Route('/statistiques', name: 'app_animal_stats', methods: ['GET'])]
    public function stats(Request $request, AnimalRepository $animalRepo, SuiviAnimalRepository $suiviRepo): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $animals = $animalRepo->findAll();
        $suivis  = $suiviRepo->findAll();

        $totalAnimaux = count($animals);
        $parEspece = [];
        $parRace   = [];
        $parSexe   = ['Mâle' => 0, 'Femelle' => 0];

        foreach ($animals as $a) {
            $parEspece[$a->getEspece()] = ($parEspece[$a->getEspece()] ?? 0) + 1;
            $parRace[$a->getRace()]     = ($parRace[$a->getRace()] ?? 0) + 1;
            $parSexe[$a->getSexe()]     = ($parSexe[$a->getSexe()] ?? 0) + 1;
        }
        arsort($parEspece);
        arsort($parRace);

        $totalSuivis = count($suivis);
        $parEtat     = ['Bon' => 0, 'Moyen' => 0, 'Mauvais' => 0];
        $parActivite = ['Faible' => 0, 'Modéré' => 0, 'Élevé' => 0];
        $tempSum = $poidsSum = $rythmeSum = 0;
        $parMois = [];

        foreach ($suivis as $s) {
            $etat = $s->getEtatSante();
            $act  = $s->getNiveauActivite();
            if (isset($parEtat[$etat]))    $parEtat[$etat]++;
            if (isset($parActivite[$act])) $parActivite[$act]++;
            $tempSum   += $s->getTemperature();
            $poidsSum  += $s->getPoids();
            $rythmeSum += $s->getRythmeCardiaque();
            $mois = $s->getDateSuivi()->format('Y-m');
            $parMois[$mois] = ($parMois[$mois] ?? 0) + 1;
        }
        ksort($parMois);
        $derniersMois = array_slice($parMois, -6, 6, true);

        // ── CMENGoogleChartsBundle — création des objets charts ──────────

        // 1. Donut — Animaux par espèce
        $chartEspece = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart();
        $especeData  = [['Espèce', 'Animaux']];
        foreach ($parEspece as $esp => $nb) {
            $especeData[] = [$esp, $nb];
        }
        $chartEspece->getData()->setArrayToDataTable($especeData);
        $chartEspece->getOptions()->setTitle('Animaux par espèce');
        $chartEspece->getOptions()->setPieHole(0.4);
        $chartEspece->getOptions()->setColors(['#3B6D11','#639922','#C0DD97','#8BC34A','#27500A']);

        // 2. Pie — Répartition par sexe
        $chartSexe = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart();
        $chartSexe->getData()->setArrayToDataTable([
            ['Sexe', 'Animaux'],
            ['Mâle',    $parSexe['Mâle']],
            ['Femelle', $parSexe['Femelle']],
        ]);
        $chartSexe->getOptions()->setTitle('Répartition par sexe');
        $chartSexe->getOptions()->setColors(['#3B6D11','#C0DD97']);

        // 3. Colonnes — État de santé
        $chartEtat = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart();
        $chartEtat->getData()->setArrayToDataTable([
            ['État', 'Suivis', ['role' => 'style']],
            ['Bon',     $parEtat['Bon'],     '#16a34a'],
            ['Moyen',   $parEtat['Moyen'],   '#ca8a04'],
            ['Mauvais', $parEtat['Mauvais'], '#dc2626'],
        ]);
        $chartEtat->getOptions()->setTitle('État de santé des suivis');
        $chartEtat->getOptions()->getLegend()->setPosition('none');

        // 4. Colonnes — Niveau d'activité
        $chartActivite = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart();
        $chartActivite->getData()->setArrayToDataTable([
            ["Activité", 'Suivis'],
            ['Faible',  $parActivite['Faible']],
            ['Modéré',  $parActivite['Modéré']],
            ['Élevé',   $parActivite['Élevé']],
        ]);
        $chartActivite->getOptions()->setTitle("Niveau d'activité");
        $chartActivite->getOptions()->setColors(['#3B6D11']);
        $chartActivite->getOptions()->getLegend()->setPosition('none');

        // 5. Ligne — Suivis par mois
        $chartMois = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart();
        $moisData  = [['Mois', 'Suivis']];
        foreach ($derniersMois as $mois => $nb) {
            $moisData[] = [$mois, $nb];
        }
        $chartMois->getData()->setArrayToDataTable($moisData);
        $chartMois->getOptions()->setTitle('Suivis par mois (6 derniers)');
        $chartMois->getOptions()->setColors(['#3B6D11']);
        $chartMois->getOptions()->getLegend()->setPosition('none');

        return $this->render('front/suivi_animal/animal/stats.html.twig', [
            'totalAnimaux'  => $totalAnimaux,
            'totalSuivis'   => $totalSuivis,
            'moyTemp'       => $totalSuivis ? round($tempSum / $totalSuivis, 1) : 0,
            'moyPoids'      => $totalSuivis ? round($poidsSum / $totalSuivis, 1) : 0,
            'moyRythme'     => $totalSuivis ? round($rythmeSum / $totalSuivis, 0) : 0,
            'parEtat'       => $parEtat,
            'parRace'       => $parRace,
            'parRaceMax'    => $parRace ? max($parRace) : 1,
            'derniersMois'  => $derniersMois,
            'chartEspece'   => $chartEspece,
            'chartSexe'     => $chartSexe,
            'chartEtat'     => $chartEtat,
            'chartActivite' => $chartActivite,
            'chartMois'     => $chartMois,
        ]);

        return $this->render('front/suivi_animal/animal/stats.html.twig', [
            'totalAnimaux'      => $totalAnimaux,
            'parEspece'         => $parEspece,
            'parEspeceLabels'   => json_encode(array_keys($parEspece)),
            'parEspeceValues'   => json_encode(array_values($parEspece)),
            'parRace'           => $parRace,
            'parRaceMax'        => $parRace ? max($parRace) : 1,
            'parSexe'           => $parSexe,
            'totalSuivis'       => $totalSuivis,
            'parEtat'           => $parEtat,
            'parActivite'       => $parActivite,
            'moyTemp'           => $totalSuivis ? round($tempSum / $totalSuivis, 1) : 0,
            'moyPoids'          => $totalSuivis ? round($poidsSum / $totalSuivis, 1) : 0,
            'moyRythme'         => $totalSuivis ? round($rythmeSum / $totalSuivis, 0) : 0,
            'derniersMois'      => $derniersMois,
            'derniersMoisLabels'=> json_encode(array_keys($derniersMois)),
            'derniersMoisValues'=> json_encode(array_values($derniersMois)),
        ]);
    }

    #[Route('/new', name: 'app_animal_new', methods: ['GET', 'POST'])]    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $animal = new Animal();
        $animal->setIdAgriculteur($sessionUser->getId());
        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($animal);
            $entityManager->flush();
            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front/suivi_animal/animal/new.html.twig', [
            'animal' => $animal,
            'form'   => $form,
        ]);
    }

    #[Route('/{idAnimal}', name: 'app_animal_show', methods: ['GET'], requirements: ['idAnimal' => '\d+'])]
    public function show(
        #[MapEntity(mapping: ['idAnimal' => 'idAnimal'])] Animal $animal
    ): Response {
        return $this->render('front/suivi_animal/animal/show.html.twig', [
            'animal' => $animal,
        ]);
    }

    #[Route('/{idAnimal}/edit', name: 'app_animal_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity(mapping: ['idAnimal' => 'idAnimal'])] Animal $animal,
        EntityManagerInterface $entityManager
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        if ($request->isMethod('POST')) {
            $animal->setCodeAnimal($request->request->get('codeAnimal'));
            $animal->setEspece($request->request->get('espece'));
            $animal->setRace($request->request->get('race'));
            $animal->setSexe($request->request->get('sexe'));
            $animal->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
            $entityManager->flush();
            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front/suivi_animal/animal/edit.html.twig', [
            'animal' => $animal,
        ]);
    }

    #[Route('/{idAnimal}/delete', name: 'app_animal_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['idAnimal' => 'idAnimal'])] Animal $animal,
        EntityManagerInterface $entityManager
    ): Response {
        $entityManager->remove($animal);
        $entityManager->flush();
        return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
    }
}
