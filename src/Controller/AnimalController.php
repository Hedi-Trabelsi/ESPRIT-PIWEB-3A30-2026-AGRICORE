<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\User;
use App\Form\AnimalType;
use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
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
    public function index(Request $request, AnimalRepository $animalRepository, PaginatorInterface $paginator): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $q      = (string) $request->query->get('q', '');
        $sortBy = (string) $request->query->get('sortBy', 'codeAnimal');
        $order  = (string) $request->query->get('order', 'ASC');

        // On récupère la Query (pas encore exécutée) au lieu d'un tableau
        $query = $animalRepository->searchQuery($q, $sortBy, $order, null);

        // Le paginator exécute la query avec LIMIT + OFFSET automatiquement
        $animals = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), // page courante (défaut : 1)
            6                                    // 6 animaux par page
        );

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

        $codeAnimal = (string) $request->query->get('codeAnimal', '');
        $espece     = (string) $request->query->get('espece', '');
        $race       = (string) $request->query->get('race', '');
        $sexe       = (string) $request->query->get('sexe', '');
        $sortBy     = (string) $request->query->get('sortBy', 'codeAnimal');
        $order      = (string) $request->query->get('order', 'ASC');

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

        $q      = (string) $request->query->get('q', '');
        $sortBy = (string) $request->query->get('sortBy', 'codeAnimal');
        $order  = (string) $request->query->get('order', 'ASC');

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

        // ── Requêtes agrégées SQL au lieu de findAll() ──────────────
        // Résout DoctrineDoctor : "Unrestricted findAll() without LIMIT"

        $totalAnimaux = $animalRepo->countAll();
        $parEspece    = $animalRepo->countByEspece();
        $parRace      = $animalRepo->countByRace();
        $parSexe      = $animalRepo->countBySexe();

        arsort($parEspece);
        arsort($parRace);

        $totalSuivis  = $suiviRepo->countAll();
        $parEtat      = $suiviRepo->countByEtatSante();
        $parActivite  = $suiviRepo->countByNiveauActivite();
        $moyennes     = $suiviRepo->getMoyennes();
        $derniersMois = $suiviRepo->countByMois(6);

        $tempSum   = $moyennes['moyTemp']   ?? 0;
        $poidsSum  = $moyennes['moyPoids']  ?? 0;
        $rythmeSum = $moyennes['moyRythme'] ?? 0;

        // ── Google Charts ────────────────────────────────────────────

        $chartEspece = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart();
        $especeData  = [['Espèce', 'Animaux']];
        foreach ($parEspece as $esp => $nb) {
            $especeData[] = [$esp, $nb];
        }
        $chartEspece->getData()->setArrayToDataTable($especeData);
        $chartEspece->getOptions()->setTitle('Animaux par espèce');
        $chartEspece->getOptions()->setPieHole(0.4);
        $chartEspece->getOptions()->setColors(['#3B6D11','#639922','#C0DD97','#8BC34A','#27500A']);

        $chartSexe = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart();
        $chartSexe->getData()->setArrayToDataTable([
            ['Sexe', 'Animaux'],
            ['Mâle',    $parSexe['Mâle']    ?? 0],
            ['Femelle', $parSexe['Femelle'] ?? 0],
        ]);
        $chartSexe->getOptions()->setTitle('Répartition par sexe');
        $chartSexe->getOptions()->setColors(['#3B6D11','#C0DD97']);

        $chartEtat = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart();
        $chartEtat->getData()->setArrayToDataTable([
            ['État', 'Suivis', ['role' => 'style']],
            ['Bon',     $parEtat['Bon']     ?? 0, '#16a34a'],
            ['Moyen',   $parEtat['Moyen']   ?? 0, '#ca8a04'],
            ['Mauvais', $parEtat['Mauvais'] ?? 0, '#dc2626'],
        ]);
        $chartEtat->getOptions()->setTitle('État de santé des suivis');
        $chartEtat->getOptions()->getLegend()->setPosition('none');

        $chartActivite = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\ColumnChart();
        $chartActivite->getData()->setArrayToDataTable([
            ["Activité", 'Suivis'],
            ['Faible',  $parActivite['Faible']  ?? 0],
            ['Modéré',  $parActivite['Modéré']  ?? 0],
            ['Élevé',   $parActivite['Élevé']   ?? 0],
        ]);
        $chartActivite->getOptions()->setTitle("Niveau d'activité");
        $chartActivite->getOptions()->setColors(['#3B6D11']);
        $chartActivite->getOptions()->getLegend()->setPosition('none');

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
            'moyTemp'       => $tempSum,
            'moyPoids'      => $poidsSum,
            'moyRythme'     => $rythmeSum,
            'parEtat'       => $parEtat,
            'parRace'       => $parRace,
            'parRaceMax'    => $parRace ? max($parRace) : 1,
            'derniersMois'  => $derniersMois,
            'chartEspece'   => $chartEspece,
            'chartSexe'     => $chartSexe,
            'chartEtat'     => $chartEtat,
            'chartActivite' => $chartActivite,
            'chartMois'     => $chartMois,
            'parEspece'         => $parEspece,
            'parEspeceLabels'   => json_encode(array_keys($parEspece)),
            'parEspeceValues'   => json_encode(array_values($parEspece)),
            'parSexe'           => $parSexe,
            'parActivite'       => $parActivite,
            'derniersMoisLabels'=> json_encode(array_keys($derniersMois)),
            'derniersMoisValues'=> json_encode(array_values($derniersMois)),
        ]);
    }

    #[Route('/new', name: 'app_animal_new', methods: ['GET', 'POST'])]    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser instanceof User || $sessionUser->getId() === null) {
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
        Request $request,
        #[MapEntity(mapping: ['idAnimal' => 'idAnimal'])] Animal $animal,
        SuiviAnimalRepository $suiviRepo
    ): Response {
        $suivis = $suiviRepo->findByAnimalLimited($animal, 20);

        // Récupérer les alertes passées via URL après création/modification suivi
        $alertesParam = $request->query->get('alertes');
        $alertes = null;
        if (is_string($alertesParam) && $alertesParam !== '') {
            $decoded = base64_decode($alertesParam);
            if ($decoded !== false) {
                $alertes = json_decode($decoded, true);
            }
        }

        return $this->render('front/suivi_animal/animal/show.html.twig', [
            'animal'  => $animal,
            'suivis'  => $suivis,
            'alertes' => $alertes,
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
            $animal->setCodeAnimal((string) $request->request->get('codeAnimal', ''));
            $animal->setEspece((string) $request->request->get('espece', ''));
            $animal->setRace((string) $request->request->get('race', ''));
            $animal->setSexe((string) $request->request->get('sexe', ''));
            $animal->setDateNaissance(new \DateTime((string) $request->request->get('dateNaissance', '')));
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
