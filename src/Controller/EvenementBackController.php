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
use Symfony\Component\Validator\Validator\ValidatorInterface;

class EvenementBackController extends AbstractController
{
    #[Route('/back/evenements', name: 'back_evenements_list')]
    public function index(Request $request, EvennementagricoleRepository $repo, ActionLogRepository $logsRepo): Response
    {
        $search = $request->query->get('search', '');
        $filter = $request->query->get('filter', 'TOUT');

        $qb = $repo->createQueryBuilder('e');

        // SEARCH
        if (!empty($search)) {
            $qb->andWhere('e.titre LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        $now = new \DateTime();

        // FILTER
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

        // Fetch action logs for events using QueryBuilder
        $logQb = $logsRepo->createQueryBuilder('al')
            ->orderBy('al.created_at', 'DESC')
            ->setMaxResults(50);
        
        $logs = $logQb->getQuery()->getResult();

        return $this->render('back/evenements/evenements.html.twig', [
            'evenements' => $events,
            'search' => $search,
            'filter' => $filter,
            'action_logs' => $logs
        ]);
    }

    #[Route('/back/evenements/{id}', name: 'back_evenements_show', requirements: ['id' => '\\d+'])]
    public function show(Evennementagricole $event, ParticipantsRepository $participantsRepo): Response
    {
        // Fetch all participants for this event
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

    #[Route('/back/evenements/delete/{id}', name: 'back_evenements_delete', requirements: ['id' => '\\d+'])]
    public function delete(Evennementagricole $event, EntityManagerInterface $em, Request $request): Response
    {
        // Log before removing
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

    #[Route('/back/evenements/add', name: 'back_evenements_add', methods: ['GET', 'POST'])]
    public function add(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $event = new Evennementagricole();

        if ($request->isMethod('POST')) {
            $dateDebutStr = $request->request->get('date_debut', '');
            $dateFinStr = $request->request->get('date_fin', '');

            $event->setTitre(trim($request->request->get('titre', '')));
            $event->setLieu(trim($request->request->get('lieu', '')));
            $event->setDescription(trim($request->request->get('description', '')));
            $event->setStatut($request->request->get('statut', 'BROUILLON'));
            $event->setFraisInscription((int) $request->request->get('frais_inscription', 0));
            $event->setCapaciteMax((int) $request->request->get('capacite_max', 0));

            if (!empty($dateDebutStr)) {
                $event->setDateDebut(new \DateTime($dateDebutStr));
            }
            if (!empty($dateFinStr)) {
                $event->setDateFin(new \DateTime($dateFinStr));
            }

            // Validate using entity Assert constraints
            $violations = $validator->validate($event);

            // Extra check: date_fin must be after date_debut
            if ($event->getDateDebut() && $event->getDateFin() && $event->getDateFin() <= $event->getDateDebut()) {
                $this->addFlash('error', 'La date de fin doit être après la date de début.');
            }

            if (count($violations) > 0 || count($this->container->get('request_stack')->getSession()->getFlashBag()->peekAll())) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                return $this->render('back/evenements/add.html.twig');
            }

            $em->persist($event);
            $em->flush();

            // Log the creation
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

        return $this->render('back/evenements/add.html.twig');
    }

    #[Route('/back/evenements/edit/{id}', name: 'back_evenements_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Evennementagricole $event, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        if ($request->isMethod('POST')) {
            $oldTitle = $event->getTitre();

            $event->setTitre(trim($request->request->get('titre', '')));
            $event->setLieu(trim($request->request->get('lieu', '')));
            $event->setDescription(trim($request->request->get('description', '')));
            $event->setStatut($request->request->get('statut', 'BROUILLON'));
            $event->setDateDebut(new \DateTime($request->request->get('date_debut')));
            $event->setDateFin(new \DateTime($request->request->get('date_fin')));
            $event->setFraisInscription((int) $request->request->get('frais_inscription'));
            $event->setCapaciteMax((int) $request->request->get('capacite_max'));

            // Validate using entity Assert constraints
            $violations = $validator->validate($event);

            if ($event->getDateFin() <= $event->getDateDebut()) {
                $this->addFlash('error', 'La date de fin doit être après la date de début.');
            }

            if (count($violations) > 0 || count($this->container->get('request_stack')->getSession()->getFlashBag()->peekAll())) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                return $this->render('back/evenements/edit.html.twig', [
                    'event' => $event
                ]);
            }

            // Log the update
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
            'event' => $event
        ]);
    }

}