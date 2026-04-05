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
        $form = $this->createForm(SuiviAnimalType::class, $suiviAnimal, [
            'idAgriculteur' => $sessionUser->getId(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($suiviAnimal);
            $entityManager->flush();
            return $this->redirectToRoute('app_suivi_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front/suivi_animal/suivi_animal/new.html.twig', [
            'suivi_animal' => $suiviAnimal,
            'form'         => $form,
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
        $form = $this->createForm(SuiviAnimalType::class, $suiviAnimal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            return $this->redirectToRoute('app_suivi_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('front/suivi_animal/suivi_animal/edit.html.twig', [
            'suivi_animal' => $suiviAnimal,
            'form'         => $form,
        ]);
    }

    #[Route('/{idSuivi}', name: 'app_suivi_animal_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        #[MapEntity(mapping: ['idSuivi' => 'idSuivi'])] SuiviAnimal $suiviAnimal,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$suiviAnimal->getIdSuivi(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($suiviAnimal);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_suivi_animal_index', [], Response::HTTP_SEE_OTHER);
    }
}
