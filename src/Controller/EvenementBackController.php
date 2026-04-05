<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Repository\EvennementagricoleRepository;
use App\Repository\ParticipantsRepository;
use App\Repository\ActionLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvenementBackController extends AbstractController
{
    #[Route('/back/evenements', name: 'back_evenements_list')]
    public function index(Request $request, EvennementagricoleRepository $repo, ActionLogRepository $logsRepo): Response
    {
        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'TOUT');

        $qb = $repo->createQueryBuilder('e');

        if (!empty($search)) {
            $qb->andWhere('e.titre LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $now = new \DateTime();

        if ($filter === 'EN_COURS') {
            $qb->andWhere('e.date_debut <= :now AND e.date_fin >= :now')->setParameter('now', $now);
        } elseif ($filter === 'COMING') {
            $qb->andWhere('e.date_debut > :now')->setParameter('now', $now);
        } elseif ($filter === 'HISTORIQUE') {
            $qb->andWhere('e.date_fin < :now')->setParameter('now', $now);
        }

        $events = $qb->getQuery()->getResult();

        try {
            $logs = $logsRepo->createQueryBuilder('al')
                ->orderBy('al.created_at', 'DESC')
                ->setMaxResults(10)
                ->getQuery()->getResult();
        } catch (\Exception $e) {
            $logs = [];
        }

        return $this->render('back/evenements/evenements.html.twig', [
            'evenements' => $events,
            'search' => $search,
            'filter' => $filter,
            'action_logs' => $logs
        ]);
    }

    #[Route('/back/evenements/add', name: 'back_evenements_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Evennementagricole();

        if ($request->isMethod('POST')) {
            $event->setTitre($request->request->get('titre'));
            $event->setLieu($request->request->get('lieu'));
            $event->setDescription($request->request->get('description', ''));
            $event->setStatut($request->request->get('statut', 'BROUILLON'));
            $event->setDateDebut(new \DateTime($request->request->get('date_debut')));
            $event->setDateFin(new \DateTime($request->request->get('date_fin')));
            $event->setFraisInscription((int) $request->request->get('frais_inscription'));
            $event->setCapaciteMax((int) $request->request->get('capacite_max'));

            $em->persist($event);
            $em->flush();

            return $this->redirectToRoute('back_evenements_list');
        }

        return $this->render('back/evenements/add.html.twig');
    }

    #[Route('/back/evenements/delete/{id}', name: 'back_evenements_delete', requirements: ['id' => '\\d+'])]
    public function delete(int $id, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Evennementagricole::class)->find($id);
        if ($event) {
            $em->remove($event);
            $em->flush();
        }

        return $this->redirectToRoute('back_evenements_list');
    }

    #[Route('/back/evenements/edit/{id}', name: 'back_evenements_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Evennementagricole::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        if ($request->isMethod('POST')) {
            $event->setTitre($request->request->get('titre'));
            $event->setLieu($request->request->get('lieu'));
            $event->setDescription($request->request->get('description', ''));
            $event->setStatut($request->request->get('statut', 'BROUILLON'));
            $event->setDateDebut(new \DateTime($request->request->get('date_debut')));
            $event->setDateFin(new \DateTime($request->request->get('date_fin')));
            $event->setFraisInscription((int) $request->request->get('frais_inscription'));
            $event->setCapaciteMax((int) $request->request->get('capacite_max'));

            $em->flush();

            return $this->redirectToRoute('back_evenements_list');
        }

        return $this->render('back/evenements/edit.html.twig', [
            'event' => $event
        ]);
    }

    #[Route('/back/evenements/{id}', name: 'back_evenements_show', requirements: ['id' => '\\d+'])]
    public function show(int $id, EntityManagerInterface $em, ParticipantsRepository $participantsRepo): Response
    {
        $event = $em->getRepository(Evennementagricole::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement introuvable.');
        }

        $participants = $participantsRepo->findBy(['evenement' => $event]);

        // Somme réelle des places réservées
        $placesReservees = 0;
        foreach ($participants as $p) {
            $placesReservees += (int) $p->getNbr_places();
        }
        $placesRestantes = max(0, $event->getCapacite_max() - $placesReservees);
        $tauxRemplissage = $event->getCapacite_max() > 0
            ? round($placesReservees / $event->getCapacite_max() * 100)
            : 0;

        return $this->render('back/evenements/show.html.twig', [
            'evenement'       => $event,
            'participants'    => $participants,
            'placesReservees' => $placesReservees,
            'placesRestantes' => $placesRestantes,
            'tauxRemplissage' => $tauxRemplissage,
        ]);
    }
}
