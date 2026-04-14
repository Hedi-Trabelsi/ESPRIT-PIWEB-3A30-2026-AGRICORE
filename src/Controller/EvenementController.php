<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Entity\Participants;
use App\Entity\User;
use App\Form\ParticipantsType;
use App\Repository\EvennementagricoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    private function buildParticipantList(Evennementagricole $ev, EntityManagerInterface $em): array
    {
        $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev]);
        $result = [];
        foreach ($participants as $p) {
            $user = $em->getRepository(User::class)->find($p->getIdUtilisateur());
            $avatar = null;
            if ($user && $user->getImage()) {
                $raw = $user->getImage();
                if (is_resource($raw)) $raw = stream_get_contents($raw);
                $avatar = 'data:image/jpeg;base64,' . base64_encode($raw);
            }
            $result[] = [
                'nom_participant' => $p->getNomParticipant(),
                'nbr_places'      => $p->getNbrPlaces(),
                'avatar'          => $avatar,
            ];
        }
        return $result;
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

        // Always exclude past events on front
        $qb->andWhere('e.date_fin >= :now')->setParameter('now', $now);

        // Status filter
        match($filter) {
            'EN_COURS'   => $qb->andWhere('e.date_debut <= :now AND e.date_fin >= :now')->setParameter('now', $now),
            'COMING'     => $qb->andWhere('e.date_debut > :now')->setParameter('now', $now),
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

            // Fetch up to 4 participant avatars
            $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev], null, 4);
            $avatarPreviews = [];
            foreach ($participants as $p) {
                $user = $em->getRepository(User::class)->find($p->getIdUtilisateur());
                if ($user && $user->getImage()) {
                    $raw = $user->getImage();
                    if (is_resource($raw)) $raw = stream_get_contents($raw);
                    $avatarPreviews[] = 'data:image/jpeg;base64,' . base64_encode($raw);
                } else {
                    $avatarPreviews[] = null; // will show initials fallback
                }
            }

            // Total participant count
            $totalParticipants = $em->getRepository(Participants::class)
                ->createQueryBuilder('p')
                ->select('COUNT(p.id_participant)')
                ->where('p.evenement = :ev')
                ->setParameter('ev', $ev)
                ->getQuery()->getSingleScalarResult();

            $data[] = [
                'evenement'        => $ev,
                'status'           => $status,
                'placesRestantes'  => max(0, $placesRestantes),
                'avatarPreviews'   => $avatarPreviews,
                'totalParticipants'=> (int) $totalParticipants,
            ];
        }

        // Past events the logged-in user participated in
        $mesEvenementsPassés = [];
        $sessionUser = $request->getSession()->get('user');
        if ($sessionUser) {
            $participations = $em->getRepository(Participants::class)->findBy([
                'id_utilisateur' => $sessionUser->getId()
            ]);
            foreach ($participations as $p) {
                $ev = $p->getEvenement();
                if ($ev && $ev->getDateFin() < $now) {
                    $mesEvenementsPassés[] = [
                        'evenement'   => $ev,
                        'nbr_places'  => $p->getNbrPlaces(),
                        'entry_code'  => $p->getEntryCode(),
                        'confirmation'=> $p->getConfirmation(),
                    ];
                }
            }
        }

        return $this->render('front/evenements/evenements.html.twig', [
            'evenements'          => $data,
            'filter'              => $filter,
            'search'              => $search,
            'dateFrom'            => $dateFrom,
            'sortPrice'           => $sortPrice,
            'budgetMax'           => (int)$budgetMax,
            'mesEvenementsPassés' => $mesEvenementsPassés,
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

        $participantConfirmation = 'pending';
        if ($sessionUser) {
            $existing = $em->getRepository(Participants::class)->findOneBy([
                'evenement' => $ev,
                'id_utilisateur' => $sessionUser->getId()
            ]);

            $dejaInscrit = $existing !== null;
            if ($existing) {
                $participantConfirmation = $existing->getConfirmation();
            }
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
            'participantConfirmation'=> $participantConfirmation,
            'isPast'                 => $isPast,
            'form'                   => $form->createView(),
            'showParticipationModal' => $form->isSubmitted(),
            'participants'           => $this->buildParticipantList($ev, $em),
        ]);
    }
#[Route('/evenement/{id}/participer', name: 'app_participer', methods: ['POST'])]
public function participer(Evennementagricole $ev, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
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

    $nbrPlaces      = (int)$request->request->get('nbr_places', 1);
    $nomParticipant = trim($request->request->get('nom_participant'));
    $emailParticipant = trim($request->request->get('email_participant', ''));
    $montantPayee   = $request->request->get('montant_payee', 0);

    if (empty($nomParticipant)) {
        $this->addFlash('error', 'Le nom du participant est obligatoire.');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    if (empty($emailParticipant) || !filter_var($emailParticipant, FILTER_VALIDATE_EMAIL)) {
        $this->addFlash('error', 'Une adresse email valide est obligatoire.');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    $totalReserved = $this->getTotalReservedPlaces($ev, $em);
    $dispo = $ev->getCapaciteMax() - $totalReserved;
    if ($nbrPlaces > $dispo) {
        $this->addFlash('error', "Désolé, il ne reste que $dispo places.");
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    $entryCode = random_int(100000, 999999);
    $confirmToken = bin2hex(random_bytes(32));

    $participant = new Participants();
    $participant->setEvenement($ev);
    $participant->setIdUtilisateur($sessionUser->getId());
    $participant->setNomParticipant($nomParticipant);
    $participant->setNbrPlaces($nbrPlaces);
    $participant->setMontantPayee((string)$montantPayee);
    $participant->setDateInscription(new \DateTime());
    $participant->setStatutParticipation("En attente");
    $participant->setEntryCode($entryCode);
    $participant->setConfirmation("pending");
    $participant->setEmail($emailParticipant);
    $participant->setConfirmToken($confirmToken);

    $em->persist($participant);
    $em->flush();

    // Send confirmation email
    try {
        $html = $this->renderView('emails/inscription_confirmation.html.twig', [
            'nom'           => $nomParticipant,
            'evenement'     => $ev->getTitre(),
            'date_debut'    => $ev->getDateDebut()?->format('d/m/Y H:i'),
            'date_fin'      => $ev->getDateFin()?->format('d/m/Y H:i'),
            'lieu'          => $ev->getLieu(),
            'nbr_places'    => $nbrPlaces,
            'montant'       => $montantPayee,
            'entry_code'    => $entryCode,
            'confirm_url'   => $this->generateUrl('app_confirmer_inscription', ['token' => $confirmToken], UrlGeneratorInterface::ABSOLUTE_URL),
            'url'           => $this->generateUrl('app_evenement_show', ['id' => $ev->getIdEv()], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $email = (new Email())
            ->from('noreply@agricore.tn')
            ->to($emailParticipant)
            ->subject('✅ Confirmation d\'inscription — ' . $ev->getTitre())
            ->html($html);

        $mailer->send($email);
        $mailStatus = "Un email de confirmation a été envoyé à $emailParticipant.";
    } catch (\Exception $e) {
        $mailStatus = "(email non envoyé : " . $e->getMessage() . ")";
    }

    $this->addFlash('info', "Demande d'inscription envoyée ! Vérifiez votre email ($emailParticipant) et cliquez sur le lien de confirmation pour finaliser votre inscription.");
    return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
}
    #[Route('/inscription/confirmer/{token}', name: 'app_confirmer_inscription')]
    public function confirmerInscription(string $token, EntityManagerInterface $em): Response
    {
        $participant = $em->getRepository(Participants::class)->findOneBy(['confirm_token' => $token]);

        if (!$participant) {
            return new Response('<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626;">Lien invalide ou déjà utilisé.</h2>');
        }

        $participant->setConfirmation('confirmed');
        $participant->setConfirmToken(null); // invalidate token
        $em->flush();

        $ev = $participant->getEvenement();
        $this->addFlash('success', '✅ Votre participation à "' . $ev->getTitre() . '" est confirmée !');
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
