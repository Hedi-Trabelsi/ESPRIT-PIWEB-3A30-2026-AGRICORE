<?php

namespace App\Controller;

use App\Entity\SuiviAnimal;
use App\Repository\AnimalRepository;
use App\Repository\SuiviAnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SuiviAnimalBackController extends AbstractController
{
    // ─── LIST ────────────────────────────────────────────────────────────────
    #[Route('/back/suivis', name: 'back_suivis')]
    public function index(Request $request, SuiviAnimalRepository $repo): Response
    {
        $q              = $request->query->get('q', '');
        $sortBy         = $request->query->get('sortBy', 'dateSuivi');
        $order          = $request->query->get('order', 'DESC');
        $filterEtat     = $request->query->get('etat', '');
        $filterActivite = $request->query->get('activite', '');

        $allowed = ['dateSuivi', 'temperature', 'poids', 'rythmeCardiaque', 'etatSante', 'niveauActivite'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'dateSuivi';
        $order   = strtoupper($order) === 'ASC' ? 'ASC' : 'DESC';

        $qb = $repo->createQueryBuilder('s')->innerJoin('s.animal', 'a');

        if ($q !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.codeAnimal',     ':q'),
                    $qb->expr()->like('s.etatSante',      ':q'),
                    $qb->expr()->like('s.niveauActivite', ':q'),
                    $qb->expr()->like('s.remarque',       ':q')
                )
            )->setParameter('q', '%'.$q.'%');
        }

        if ($filterEtat !== '') {
            $qb->andWhere('s.etatSante = :etat')->setParameter('etat', $filterEtat);
        }

        if ($filterActivite !== '') {
            $qb->andWhere('s.niveauActivite = :activite')->setParameter('activite', $filterActivite);
        }

        $suivis = $qb->orderBy('s.'.$sortBy, $order)->getQuery()->getResult();

        return $this->render('back/suivi_animal/suivi/index.html.twig', [
            'suivis'         => $suivis,
            'q'              => $q,
            'sortBy'         => $sortBy,
            'order'          => $order,
            'filterEtat'     => $filterEtat,
            'filterActivite' => $filterActivite,
        ]);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    #[Route('/back/suivis/{id}', name: 'back_suivi_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(SuiviAnimal $suivi): Response
    {
        return $this->render('back/suivi_animal/suivi/show.html.twig', [
            'suivi' => $suivi,
        ]);
    }

    // ─── NEW ──────────────────────────────────────────────────────────────────
    #[Route('/back/suivis/new', name: 'back_suivi_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, AnimalRepository $animalRepo): Response
    {
        $animals = $animalRepo->findAll();

        if ($request->isMethod('POST')) {
            $animal = $animalRepo->find($request->request->get('idAnimal'));

            if (!$animal) {
                $this->addFlash('error', 'Animal introuvable.');
                return $this->redirectToRoute('back_suivi_new');
            }

            $suivi = new SuiviAnimal();
            $suivi->setAnimal($animal);
            $suivi->setDateSuivi(new \DateTime($request->request->get('dateSuivi')));
            $suivi->setTemperature((float) $request->request->get('temperature'));
            $suivi->setPoids((float) $request->request->get('poids'));
            $suivi->setRythmeCardiaque((int) $request->request->get('rythmeCardiaque'));
            $suivi->setNiveauActivite($request->request->get('niveauActivite'));
            $suivi->setEtatSante($request->request->get('etatSante'));
            $suivi->setRemarque($request->request->get('remarque'));

            $em->persist($suivi);
            $em->flush();

            $this->addFlash('success', 'Suivi ajouté avec succès.');
            return $this->redirectToRoute('back_suivis');
        }

        return $this->render('back/suivi_animal/suivi/new.html.twig', [
            'animals'         => $animals,
            'preselectedAnimal' => $request->query->get('idAnimal'),
        ]);
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────
    #[Route('/back/suivis/{id}/edit', name: 'back_suivi_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(SuiviAnimal $suivi, Request $request, EntityManagerInterface $em, AnimalRepository $animalRepo): Response
    {
        $animals = $animalRepo->findAll();

        if ($request->isMethod('POST')) {
            $animal = $animalRepo->find($request->request->get('idAnimal'));
            if ($animal) {
                $suivi->setAnimal($animal);
            }
            $suivi->setDateSuivi(new \DateTime($request->request->get('dateSuivi')));
            $suivi->setTemperature((float) $request->request->get('temperature'));
            $suivi->setPoids((float) $request->request->get('poids'));
            $suivi->setRythmeCardiaque((int) $request->request->get('rythmeCardiaque'));
            $suivi->setNiveauActivite($request->request->get('niveauActivite'));
            $suivi->setEtatSante($request->request->get('etatSante'));
            $suivi->setRemarque($request->request->get('remarque'));

            $em->flush();

            $this->addFlash('success', 'Suivi modifié avec succès.');
            return $this->redirectToRoute('back_suivis');
        }

        return $this->render('back/suivi_animal/suivi/edit.html.twig', [
            'suivi'   => $suivi,
            'animals' => $animals,
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────
    #[Route('/back/suivis/{id}/delete', name: 'back_suivi_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(SuiviAnimal $suivi, EntityManagerInterface $em): Response
    {
        $em->remove($suivi);
        $em->flush();

        $this->addFlash('success', 'Suivi supprimé.');
        return $this->redirectToRoute('back_suivis');
    }
}
