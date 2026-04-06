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

        $animals = $animalRepository->search($q, $sortBy, $order, $sessionUser->getId());

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

        $animals = $animalRepository->searchStatic($codeAnimal, $espece, $race, $sexe, $sortBy, $order, $sessionUser->getId());

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

        $animals = $animalRepository->search($q, $sortBy, $order, $sessionUser->getId());

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

        $idAgriculteur = $sessionUser->getId();
        $animals = $animalRepo->findBy(['idAgriculteur' => $idAgriculteur]);

        // ── Animaux stats ──────────────────────────────────────────────
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

        // ── Suivis stats ───────────────────────────────────────────────
        $suivis = $suiviRepo->search('', 'dateSuivi', 'DESC', $idAgriculteur);
        $totalSuivis = count($suivis);

        $parEtat     = ['Bon' => 0, 'Moyen' => 0, 'Mauvais' => 0];
        $parActivite = ['Faible' => 0, 'Modéré' => 0, 'Élevé' => 0];
        $tempSum = $poidsSum = $rythmeSum = 0;
        $parMois = [];

        foreach ($suivis as $s) {
            $parEtat[$s->getEtatSante()]         = ($parEtat[$s->getEtatSante()] ?? 0) + 1;
            $parActivite[$s->getNiveauActivite()] = ($parActivite[$s->getNiveauActivite()] ?? 0) + 1;
            $tempSum   += $s->getTemperature();
            $poidsSum  += $s->getPoids();
            $rythmeSum += $s->getRythmeCardiaque();
            $mois = $s->getDateSuivi()->format('Y-m');
            $parMois[$mois] = ($parMois[$mois] ?? 0) + 1;
        }

        ksort($parMois);
        $derniersMois = array_slice($parMois, -6, 6, true);

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

    #[Route('/{idAnimal}', name: 'app_animal_show', methods: ['GET'])]
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
