<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Entity\Participants;
use App\Form\ParticipantsType;
use App\Repository\EvennementagricoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvenementController extends AbstractController
{
    private function getTotalReservedPlaces(Evennementagricole $ev, EntityManagerInterface $em): int
    {
        return (int) $em->getRepository(Participants::class)
            ->createQueryBuilder('p')
            ->select('SUM(p.nbr_places)')
            ->where('p.evenement = :ev')
            ->setParameter('ev', $ev)
            ->getQuery()
            ->getSingleScalarResult() ?: 0;
    }

    #[Route('/evenements', name: 'app_evenement')]
    public function index(Request $request, EvennementagricoleRepository $repo, EntityManagerInterface $em): Response
    {
        $filter   = $request->query->get('filter', 'TOUT');
        $search   = trim($request->query->get('search', ''));
        $dateFrom = $request->query->get('date_from', '');
        $sortPrice= $request->query->get('sort_price', 'none');
        $budgetMax= $request->query->get('budget_max', 500);
        $now      = new \DateTime();

        $qb = $repo->createQueryBuilder('e');

        // Status filter
        match($filter) {
            'EN_COURS'   => $qb->andWhere('e.date_debut <= :now AND e.date_fin >= :now')->setParameter('now', $now),
            'COMING'     => $qb->andWhere('e.date_debut > :now')->setParameter('now', $now),
            'HISTORIQUE' => $qb->andWhere('e.date_fin < :now')->setParameter('now', $now),
            default      => null,
        };

        // Search
        if ($search !== '') {
            $qb->andWhere('e.titre LIKE :search OR e.lieu LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Date from
        if ($dateFrom !== '') {
            try {
                $qb->andWhere('e.date_debut >= :dateFrom')
                   ->setParameter('dateFrom', new \DateTime($dateFrom));
            } catch (\Exception) {}
        }

        // Budget max
        if ((int)$budgetMax < 500) {
            $qb->andWhere('e.frais_inscription <= :budget')
               ->setParameter('budget', (int)$budgetMax);
        }

        // Sort by price
        if ($sortPrice === 'asc')  $qb->orderBy('e.frais_inscription', 'ASC');
        if ($sortPrice === 'desc') $qb->orderBy('e.frais_inscription', 'DESC');

        $evenements = $qb->getQuery()->getResult();
        $data = [];

        foreach ($evenements as $ev) {
            if ($ev->getDateFin() < $now)       $status = 'HISTORIQUE';
            elseif ($ev->getDateDebut() > $now) $status = 'COMING';
            else                                $status = 'EN_COURS';

            $totalReserved   = $this->getTotalReservedPlaces($ev, $em);
            $placesRestantes = $ev->getCapaciteMax() - $totalReserved;

            $data[] = [
                'evenement'       => $ev,
                'status'          => $status,
                'placesRestantes' => max(0, $placesRestantes),
            ];
        }

        return $this->render('front/evenements/evenements.html.twig', [
            'evenements' => $data,
            'filter'     => $filter,
            'search'     => $search,
            'dateFrom'   => $dateFrom,
            'sortPrice'  => $sortPrice,
            'budgetMax'  => (int)$budgetMax,
        ]);
    }

    #[Route('/evenement/{id}', name: 'app_evenement_show')]
    public function show(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
    {
        $totalReserved = $this->getTotalReservedPlaces($ev, $em);
        $placesRestantes = $ev->getCapaciteMax() - $totalReserved;

        $participant = new Participants();
        $form = $this->createForm(ParticipantsType::class, $participant);
        $form->handleRequest($request);

        $sessionUser = $request->getSession()->get('user');
        $dejaInscrit = false;

        if ($sessionUser) {
            $existing = $em->getRepository(Participants::class)->findOneBy([
                'evenement' => $ev,
                'id_utilisateur' => $sessionUser->getId()
            ]);

            $dejaInscrit = $existing !== null;
        }

        $now = new \DateTime();
        $isPast = $ev->getDateFin() < $now;

        if ($form->isSubmitted() && $form->isValid()) {

            if (!$sessionUser) {
                $this->addFlash('error', 'Vous devez être connecté.');
                return $this->redirectToRoute('front_login');
            }

            if ($isPast) {
                $this->addFlash('error', 'Cet événement est terminé, inscription impossible.');
                return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
            }

            $nbrPlaces = $participant->getNbrPlaces();
            if ($nbrPlaces < 1) {
                $nbrPlaces = 1;
            }

            $dispo = $ev->getCapaciteMax() - $totalReserved;

            if ($nbrPlaces > $dispo) {
                $this->addFlash('error', "Seulement $dispo places disponibles.");
            } else {

                if (!$participant->getNomParticipant()) {
                    $participant->setNomParticipant(
                        $sessionUser->getPrenom() . ' ' . $sessionUser->getNom()
                    );
                }

                $participant->setEvenement($ev);
                $participant->setIdUtilisateur($sessionUser->getId());
                $participant->setDateInscription(new \DateTime());
                $participant->setStatutParticipation("En attente");
                $participant->setEntryCode(random_int(100000, 999999));

                $montant = $nbrPlaces * (float) $ev->getFraisInscription();
                $participant->setMontantPayee((string) $montant);
                $participant->setConfirmation("pending");

                $em->persist($participant);
                $em->flush();

                $this->addFlash('success', "Inscription réussie !");
                return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
            }
        } elseif ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('error', 'Veuillez corriger les erreurs du formulaire.');
        }

        return $this->render('front/evenements/show.html.twig', [
            'evenement'              => $ev,
            'placesRestantes'        => max(0, $placesRestantes),
            'dejaInscrit'            => $dejaInscrit,
            'isPast'                 => $isPast,
            'form'                   => $form->createView(),
            'showParticipationModal' => $form->isSubmitted(),
        ]);
    }
#[Route('/evenement/{id}/participer', name: 'app_participer', methods: ['POST'])]
public function participer(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
{
    $sessionUser = $request->getSession()->get('user');
    if (!$sessionUser) {
        $this->addFlash('error', 'Vous devez être connecté.');
        return $this->redirectToRoute('front_login');
    }

    if ($ev->getDateFin() < new \DateTime()) {
        $this->addFlash('error', 'Cet événement est terminé, inscription impossible.');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    // Récupération et nettoyage des données
    $nbrPlaces = (int)$request->request->get('nbr_places', 1);
    $nomParticipant = trim($request->request->get('nom_participant'));
    $montantPayee = $request->request->get('montant_payee', 0);

    // --- VÉRIFICATION CRUCIALE ---
    if (empty($nomParticipant)) {
        $this->addFlash('error', 'Le nom du participant est obligatoire pour valider l\'inscription.');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    // Vérification de la capacité restante (sécurité côté serveur)
    $totalReserved = $this->getTotalReservedPlaces($ev, $em);
    $dispo = $ev->getCapaciteMax() - $totalReserved;
    if ($nbrPlaces > $dispo) {
        $this->addFlash('error', "Désolé, il ne reste que $dispo places.");
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    $participant = new Participants();
    $participant->setEvenement($ev);
    $participant->setIdUtilisateur($sessionUser->getId());
    $participant->setNomParticipant($nomParticipant);
    $participant->setNbrPlaces($nbrPlaces);
    $participant->setMontantPayee((string)$montantPayee);
    $participant->setDateInscription(new \DateTime());
    $participant->setStatutParticipation("En attente");
    $participant->setEntryCode(random_int(100000, 999999));
    $participant->setConfirmation("pending");

    $em->persist($participant);
    $em->flush();

    $this->addFlash('success', "Inscription réussie pour " . $ev->getTitre() . " !");
    return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
}
    #[Route('/evenement/{id}/annuler', name: 'app_annuler_inscription', methods: ['POST'])]
    public function annulerInscription(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
    {
        $user = $request->getSession()->get('user');

        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        $participant = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev,
            'id_utilisateur' => $user->getId()
        ]);

        if ($participant) {
            $em->remove($participant);
            $em->flush();
            $this->addFlash('success', 'Inscription annulée.');
        }

        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }
}
