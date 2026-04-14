<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Form\EvennementagricoleType;
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
    // =========================
    // LIST
    // =========================
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

        if ($filter === 'TOUT') {
            // Exclude past events from default view
            $qb->andWhere('e.date_fin >= :now')->setParameter('now', $now);
        }

        if ($filter === 'EN_COURS') {
            $qb->andWhere('e.date_debut <= :now AND e.date_fin >= :now')
               ->setParameter('now', $now);
        }

        if ($filter === 'COMING') {
            $qb->andWhere('e.date_debut > :now')
               ->setParameter('now', $now);
        }

        if ($filter === 'HISTORIQUE') {
            $qb->andWhere('e.date_fin < :now')
               ->setParameter('now', $now);
        }

        $events = $qb->getQuery()->getResult();

        $logs = $logsRepo->createQueryBuilder('al')
            ->orderBy('al.created_at', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        return $this->render('back/evenements/evenements.html.twig', [
            'evenements' => $events,
            'search' => $search,
            'filter' => $filter,
            'action_logs' => $logs
        ]);
    }

    // =========================
    // PARTICIPANTS MANAGEMENT
    // =========================
    #[Route('/back/evenements/{id}/participants', name: 'back_evenements_participants', requirements: ['id' => '\\d+'])]
    public function participants(Evennementagricole $event, ParticipantsRepository $participantsRepo): Response
    {
        $participants = $participantsRepo->findBy(['evenement' => $event]);
        return $this->render('back/evenements/participants.html.twig', [
            'evenement'    => $event,
            'participants' => $participants,
        ]);
    }

    // =========================
    // MARK ATTENDED
    // =========================
    #[Route('/back/participants/{id}/attend', name: 'back_participant_attend', methods: ['POST'])]
    public function markAttended(int $id, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $participant = $em->getRepository(\App\Entity\Participants::class)->find($id);
        if (!$participant) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Not found'], 404);
        }
        $participant->setConfirmation('attended');
        $em->flush();
        return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => true]);
    }

    // =========================
    // SHOW
    // =========================
    #[Route('/back/evenements/{id}', name: 'back_evenements_show', requirements: ['id' => '\\d+'])]
    public function show(Evennementagricole $event, ParticipantsRepository $participantsRepo): Response
    {
        $participants = $participantsRepo->findBy(['evenement' => $event]);

        $placesReservees = $participantsRepo->countPlacesByEvent($event->getId_ev());
        $placesRestantes = $event->getCapacite_max() - $placesReservees;

        $tauxRemplissage = $event->getCapacite_max() > 0
            ? round(($placesReservees / $event->getCapacite_max()) * 100, 1)
            : 0;

        return $this->render('back/evenements/show.html.twig', [
            'evenement' => $event,
            'participants' => $participants,
            'placesReservees' => $placesReservees,
            'placesRestantes' => $placesRestantes,
            'tauxRemplissage' => $tauxRemplissage,
        ]);
    }

    // =========================
    // DELETE
    // =========================
    #[Route('/back/evenements/delete/{id}', name: 'back_evenements_delete', requirements: ['id' => '\\d+'])]
    public function delete(Evennementagricole $event, EntityManagerInterface $em, Request $request): Response
    {
        $sessionUser = $request->getSession()->get('user');

        $log = new \App\Entity\ActionLog();
        $log->setAction_type('DELETE');
        $log->setTarget_table('evennementagricole');
        $log->setTarget_id($event->getId_ev());
        $log->setDescription('Suppression de l\'événement : "' . $event->getTitre() . '"');
        $log->setOld_value(json_encode(['titre' => $event->getTitre()]));
        $log->setNew_value(json_encode(['titre' => 'supprimé']));
        $log->setUser_id($sessionUser ? $sessionUser->getId() : 0);
        $log->setCreated_at(new \DateTime());

        $em->persist($log);

        $em->remove($event);
        $em->flush();

        return $this->redirectToRoute('back_evenements_list');
    }

    // =========================
    // ADD (FIXED WITH FORM + isValid)
    // =========================
    #[Route('/back/evenements/add', name: 'back_evenements_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Evennementagricole();

        $form = $this->createForm(EvennementagricoleType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($event);
            $em->flush();

            // Save poster image if provided (stored as base64 in DB)
            // Read directly from $_POST to avoid Symfony request size limits
            $posterData = $_POST['poster_image_data'] ?? $request->request->get('poster_image_data', '');
            if (!empty($posterData) && str_starts_with($posterData, 'data:image')) {
                $event->setImage($posterData);
                $em->flush();
            }

            // LOG CREATE
            $sessionUser = $request->getSession()->get('user');

            $log = new \App\Entity\ActionLog();
            $log->setAction_type('CREATE');
            $log->setTarget_table('evennementagricole');
            $log->setTarget_id($event->getId_ev());
            $log->setDescription('Création de l\'événement : "' . $event->getTitre() . '"');
            $log->setOld_value(json_encode(['titre' => 'nouveau']));
            $log->setNew_value(json_encode(['titre' => $event->getTitre()]));
            $log->setUser_id($sessionUser ? $sessionUser->getId() : 0);
            $log->setCreated_at(new \DateTime());

            $em->persist($log);
            $em->flush();

            return $this->redirectToRoute('back_evenements_list');
        }

        return $this->render('back/evenements/add.html.twig', [
            'form' => $form->createView()
        ]);
    }

    // =========================
    // EDIT (FIXED WITH FORM + isValid)
    // =========================
    #[Route('/back/evenements/edit/{id}', name: 'back_evenements_edit', methods: ['GET', 'POST'])]
    public function edit(Evennementagricole $event, Request $request, EntityManagerInterface $em): Response
    {
        $oldTitle = $event->getTitre();

        $form = $this->createForm(EvennementagricoleType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->flush();

            // LOG UPDATE
            $sessionUser = $request->getSession()->get('user');

            $log = new \App\Entity\ActionLog();
            $log->setAction_type('UPDATE');
            $log->setTarget_table('evennementagricole');
            $log->setTarget_id($event->getId_ev());
            $log->setDescription('Modification de l\'événement : "' . $event->getTitre() . '"');
            $log->setOld_value(json_encode(['titre' => $oldTitle]));
            $log->setNew_value(json_encode(['titre' => $event->getTitre()]));
            $log->setUser_id($sessionUser ? $sessionUser->getId() : 0);
            $log->setCreated_at(new \DateTime());

            $em->persist($log);
            $em->flush();

            return $this->redirectToRoute('back_evenements_list');
        }

        return $this->render('back/evenements/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }
}
