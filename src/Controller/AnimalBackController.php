<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\SuiviAnimal;
use App\Repository\AnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AnimalBackController extends AbstractController
{
    // ─── LIST ────────────────────────────────────────────────────────────────
    #[Route('/back/animaux', name: 'back_animaux')]
    public function index(Request $request, AnimalRepository $repo): Response
    {
        $q      = $request->query->get('q', '');
        $sortBy = $request->query->get('sortBy', 'codeAnimal');
        $order  = $request->query->get('order', 'ASC');

        $allowed = ['codeAnimal', 'espece', 'race', 'sexe', 'dateNaissance'];
        $sortBy  = in_array($sortBy, $allowed) ? $sortBy : 'codeAnimal';
        $order   = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';

        $qb = $repo->createQueryBuilder('a');

        if ($q !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('a.codeAnimal', ':q'),
                    $qb->expr()->like('a.espece',     ':q'),
                    $qb->expr()->like('a.race',       ':q')
                )
            )->setParameter('q', '%'.$q.'%');
        }

        $animals = $qb->orderBy('a.'.$sortBy, $order)->getQuery()->getResult();

        return $this->render('back/suivi_animal/animal/index.html.twig', [
            'animals' => $animals,
            'q'       => $q,
            'sortBy'  => $sortBy,
            'order'   => $order,
        ]);
    }

    // ─── SHOW ─────────────────────────────────────────────────────────────────
    #[Route('/back/animaux/{id}', name: 'back_animal_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Animal $animal): Response
    {
        return $this->render('back/suivi_animal/animal/show.html.twig', [
            'animal' => $animal,
            'suivis' => $animal->getSuivis(),
        ]);
    }

    // ─── NEW ──────────────────────────────────────────────────────────────────
    #[Route('/back/animaux/new', name: 'back_animal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $animal = new Animal();
            $animal->setCodeAnimal($request->request->get('codeAnimal'));
            $animal->setEspece($request->request->get('espece'));
            $animal->setRace($request->request->get('race'));
            $animal->setSexe($request->request->get('sexe'));
            $animal->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
            $animal->setIdAgriculteur((int) $request->request->get('idAgriculteur'));

            $em->persist($animal);
            $em->flush();

            $this->addFlash('success', 'Animal ajouté avec succès.');
            return $this->redirectToRoute('back_animaux');
        }

        return $this->render('back/suivi_animal/animal/new.html.twig');
    }

    // ─── EDIT ─────────────────────────────────────────────────────────────────
    #[Route('/back/animaux/{id}/edit', name: 'back_animal_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Animal $animal, Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $animal->setCodeAnimal($request->request->get('codeAnimal'));
            $animal->setEspece($request->request->get('espece'));
            $animal->setRace($request->request->get('race'));
            $animal->setSexe($request->request->get('sexe'));
            $animal->setDateNaissance(new \DateTime($request->request->get('dateNaissance')));
            $animal->setIdAgriculteur((int) $request->request->get('idAgriculteur'));

            $em->flush();

            $this->addFlash('success', 'Animal modifié avec succès.');
            return $this->redirectToRoute('back_animaux');
        }

        return $this->render('back/suivi_animal/animal/edit.html.twig', [
            'animal' => $animal,
        ]);
    }

    // ─── DELETE ───────────────────────────────────────────────────────────────
    #[Route('/back/animaux/{id}/delete', name: 'back_animal_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Animal $animal, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$animal->getIdAnimal(), $request->request->get('_token'))) {
            $em->remove($animal);
            $em->flush();
            $this->addFlash('success', 'Animal supprimé.');
        }

        return $this->redirectToRoute('back_animaux');
    }
}
