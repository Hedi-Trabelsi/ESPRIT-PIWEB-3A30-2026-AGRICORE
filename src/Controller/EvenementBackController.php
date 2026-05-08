<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Entity\User;
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
    public function index(Request $request, EvennementagricoleRepository $repo, ActionLogRepository $logsRepo, EntityManagerInterface $em): Response
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

        $rawEvents = $qb->getQuery()->getResult();
        $events = is_array($rawEvents)
            ? array_values(array_filter($rawEvents, fn($r) => $r instanceof \App\Entity\Evennementagricole))
            : [];

        // Count waitlist per event
        $waitlistCounts = [];
        foreach ($events as $ev) {
            $count = $em->getRepository(\App\Entity\Participants::class)
                ->createQueryBuilder('p')
                ->select('COUNT(p.id_participant)')
                ->where('p.evenement = :ev AND p.statut_participation = :ws')
                ->setParameter('ev', $ev)
                ->setParameter('ws', 'waitlist')
                ->getQuery()->getSingleScalarResult();
            $evId = $ev->getIdEv();
            if ($evId !== null && is_numeric($count) && (int)$count > 0) {
                $waitlistCounts[$evId] = (int)$count;
            }
        }

        $logs = $logsRepo->createQueryBuilder('al')
            ->orderBy('al.created_at', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();

        return $this->render('back/evenements/evenements.html.twig', [
            'evenements'     => $events,
            'search'         => $search,
            'filter'         => $filter,
            'action_logs'    => $logs,
            'waitlistCounts' => $waitlistCounts,
        ]);
    }

    // =========================
    // 1. PARTICIPANTS MANAGEMENT (Affichage & Sync initiale)
    // =========================
    #[Route('/back/evenements/{id}/participants', name: 'back_evenements_participants', requirements: ['id' => '\\d+'])]
    public function participants(Evennementagricole $event, ParticipantsRepository $participantsRepo, EntityManagerInterface $em): Response
    {
        $participants = $participantsRepo->createQueryBuilder('p')
            ->where('p.evenement = :ev')
            ->andWhere('p.confirmation != :pending')
            ->setParameter('ev', $event)
            ->setParameter('pending', 'pending')
            ->getQuery()
            ->getResult();

        // --- SYNCHRONISATION DES STATUTS (WEB & DESKTOP) ---
        foreach ($participants as $p) {
            // Si le participant est marqué comme présent, on s'assure
            // que confirmation = 'OUI' pour ne pas redemander le code
            if ($p->getStatutParticipation() === 'attended') {
                $p->setConfirmation('OUI');
            }

            // Migration : si confirm_token est vide mais nbr_presents > 0,
            // on initialise le token au format pipe pour le jour 1
            if (!$p->getConfirmToken() && $p->getNbrPresents() > 0) {
                $p->setPresenceData(1, $p->getNbrPresents());
            }
        }
        $em->flush();

        // --- CALCUL DE LA DURÉE RÉELLE ---
        $nbrJours = 1;
        $dateDeb  = $event->getDateDebut();
        $dateFin  = $event->getDateFin();

        if ($dateDeb && $dateFin && $dateFin >= $dateDeb) {
            $diff     = $dateDeb->diff($dateFin);
            $nbrJours = (int) $diff->format('%a') + 1;
        }

        return $this->render('back/evenements/participants.html.twig', [
            'evenement'    => $event,
            'participants' => $participants,
            'nbrJours'     => $nbrJours,
        ]);
    }

    // =========================
    // 2. MARK ATTENDED (Vérification initiale du code)
    // =========================
    #[Route('/back/participants/{id}/attend', name: 'back_participant_attend', methods: ['POST'])]
    public function markAttended(int $id, Request $request, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $participant = $em->getRepository(\App\Entity\Participants::class)->find($id);
        if (!$participant) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Not found'], 404);
        }

        $submittedCode = (int) $request->request->get('code');

        // Vérification par rapport au code stocké en DB
        if ($submittedCode !== $participant->getEntry_code()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'error' => 'Code incorrect']);
        }

        // setPresenceData(day, count) écrit dans confirm_token ET met à jour nbr_presents
        // car day === 1 → nbr_presents = 1 (voir entity)
        $participant->setPresenceData(1, 1);
        $participant->setConfirmation('OUI');
        $participant->setStatutParticipation('attended');

        $em->flush();

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'success'       => true,
            'presence_data' => $participant->getPresenceData(),
            'nbr_presents'  => $participant->getNbrPresents(),
            'status'        => 'OUI',
        ]);
    }

    // =========================
    // 3. UPDATE ATTENDANCE (Gestion multi-jours + Reset)
    // =========================
    #[Route('/back/participants/{id}/update-attendance', name: 'back_participant_update_attendance', methods: ['POST'])]
    public function updateAttendance(int $id, Request $request, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $participant = $em->getRepository(\App\Entity\Participants::class)->find($id);
        if (!$participant) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['error' => 'Not found'], 404);
        }

        // --- CAS RESET COMPLET ---
        // On efface confirm_token (données multi-jours) et on remet tout à zéro
        $resetAll = $request->request->get('reset_all');
        if ($resetAll) {
            $participant->setConfirmToken(null);
            $participant->setNbrPresents(0);
            $participant->setConfirmation('confirmed');
            $participant->setStatutParticipation('confirmed');
            $em->flush();

            return new \Symfony\Component\HttpFoundation\JsonResponse([
                'success'       => true,
                'presence_data' => [],
                'nbr_presents'  => 0,
                'status'        => 'confirmed',
            ]);
        }

        // --- CAS MISE À JOUR D'UN JOUR ---
        $count = (int) $request->request->get('count');
        $day   = (int) $request->request->get('day', 1);

        // Met à jour le jour concerné dans confirm_token
        // Si day === 1, met aussi à jour nbr_presents automatiquement (voir entity)
        $participant->setPresenceData($day, $count);

        // Recalculer le total toutes journées confondues depuis confirm_token
        $totalPresents = array_sum($participant->getPresenceData());
        $participant->setNbrPresents($totalPresents);

        // Garder 'OUI' pour le Desktop
        $participant->setConfirmation('OUI');

        // Statut Web : 'attended' si au moins 1 présent sur n'importe quel jour
        $participant->setStatutParticipation($totalPresents > 0 ? 'attended' : 'confirmed');

        $em->flush();

        return new \Symfony\Component\HttpFoundation\JsonResponse([
            'success'       => true,
            'presence_data' => $participant->getPresenceData(),
            'nbr_presents'  => $totalPresents,
            'status'        => 'OUI',
        ]);
    }

    // =========================
    // SHOW
    // =========================
    #[Route('/back/evenements/{id}', name: 'back_evenements_show', requirements: ['id' => '\\d+'])]
    public function show(Evennementagricole $event, ParticipantsRepository $participantsRepo): Response
    {
        $participants = $participantsRepo->findBy(['evenement' => $event]);

        $placesReservees = $participantsRepo->countPlacesByEvent((int) $event->getId_ev());
        $placesRestantes = $event->getCapacite_max() - $placesReservees;

        $tauxRemplissage = $event->getCapacite_max() > 0
            ? round(($placesReservees / $event->getCapacite_max()) * 100, 1)
            : 0;

        return $this->render('back/evenements/show.html.twig', [
            'evenement'       => $event,
            'participants'    => $participants,
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
        $log->setTarget_id((int) $event->getId_ev());
        $log->setDescription('Suppression de l\'événement : "' . $event->getTitre() . '"');
        $log->setOld_value((string) json_encode(['titre' => $event->getTitre()]));
        $log->setNew_value((string) json_encode(['titre' => 'supprimé']));
        $log->setUser_id($sessionUser instanceof User ? (int) $sessionUser->getId() : 0);
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
            $posterRaw  = $_POST['poster_image_data'] ?? $request->request->get('poster_image_data', '');
            $posterData = is_string($posterRaw) ? $posterRaw : '';
            if (!empty($posterData) && str_starts_with($posterData, 'data:image')) {
                $event->setImage($posterData);
                $em->flush();
            }

            // LOG CREATE
            $sessionUser = $request->getSession()->get('user');

            $log = new \App\Entity\ActionLog();
            $log->setAction_type('CREATE');
            $log->setTarget_table('evennementagricole');
            $log->setTarget_id((int) $event->getId_ev());
            $log->setDescription('Création de l\'événement : "' . $event->getTitre() . '"');
            $log->setOld_value((string) json_encode(['titre' => 'nouveau']));
            $log->setNew_value((string) json_encode(['titre' => $event->getTitre()]));
            $log->setUser_id($sessionUser instanceof User ? (int) $sessionUser->getId() : 0);
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
    public function edit(Evennementagricole $event, Request $request, EntityManagerInterface $em, ParticipantsRepository $participantsRepo): Response
    {
        $oldTitle = $event->getTitre();

        $form = $this->createForm(EvennementagricoleType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Validate capacity >= current confirmed participants
            $placesReservees = $participantsRepo->countPlacesByEvent((int) $event->getId_ev());
            if ($event->getCapaciteMax() < $placesReservees) {
                $form->get('capacite_max')->addError(
                    new \Symfony\Component\Form\FormError(
                        "Impossible : {$placesReservees} place(s) sont déjà réservées. La capacité minimale est {$placesReservees}."
                    )
                );
            } else {
                $em->flush();

                $sessionUser = $request->getSession()->get('user');
                $log = new \App\Entity\ActionLog();
                $log->setAction_type('UPDATE');
                $log->setTarget_table('evennementagricole');
                $log->setTarget_id((int) $event->getId_ev());
                $log->setDescription('Modification de l\'événement : "' . $event->getTitre() . '"');
                $log->setOld_value((string) json_encode(['titre' => $oldTitle]));
                $log->setNew_value((string) json_encode(['titre' => $event->getTitre()]));
                $log->setUser_id($sessionUser instanceof User ? (int) $sessionUser->getId() : 0);
                $log->setCreated_at(new \DateTime());
                $em->persist($log);
                $em->flush();

                return $this->redirectToRoute('back_evenements_list');
            }
        }

        return $this->render('back/evenements/edit.html.twig', [
            'form'  => $form->createView(),
            'event' => $event
        ]);
    }
}