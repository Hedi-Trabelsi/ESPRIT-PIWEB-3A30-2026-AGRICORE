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
            ->andWhere('p.statut_participation != :waitlist')
            ->setParameter('ev', $ev)
            ->setParameter('waitlist', 'waitlist')
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
    public function show(Evennementagricole $ev, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $totalReserved = $this->getTotalReservedPlaces($ev, $em);
        $placesRestantes = max(0, $ev->getCapaciteMax() - $totalReserved);

        $sessionUser = $request->getSession()->get('user');
        $dejaInscrit = false;
        $onWaitlist  = false;
        $participantConfirmation = 'pending';

        if ($sessionUser) {
            $allParticipations = $em->getRepository(Participants::class)->findBy([
                'evenement' => $ev, 'id_utilisateur' => $sessionUser->getId()
            ]);
            foreach ($allParticipations as $p) {
                if ($p->getStatutParticipation() !== 'waitlist') {
                    $dejaInscrit = true;
                    $participantConfirmation = $p->getConfirmation();
                    break;
                }
            }
            if (!$dejaInscrit && count($allParticipations) > 0) {
                $onWaitlist = true;
            }
        }

        $now    = new \DateTime();
        $isPast = $ev->getDateFin() < $now;

        // Conflict detection — only flag if the overlap period hasn't fully passed
        $conflictingEvent = null;
        if ($sessionUser && !$dejaInscrit && !$isPast) {
            foreach ($em->getRepository(Participants::class)->findBy(['id_utilisateur' => $sessionUser->getId()]) as $p) {
                $other = $p->getEvenement();
                if (!$other || $other->getIdEv() === $ev->getIdEv()) continue;
                if ($p->getStatutParticipation() === 'waitlist') continue;
                // Overlap exists AND the other event hasn't fully ended yet
                if ($other->getDateDebut() <= $ev->getDateFin()
                    && $other->getDateFin() >= $ev->getDateDebut()
                    && $other->getDateFin() >= $now) {
                    $conflictingEvent = $other;
                    break;
                }
            }
        }

        $participant = new Participants();
        $form = $this->createForm(ParticipantsType::class, $participant, [
            'max_places' => $placesRestantes,
            'action'     => $this->generateUrl('app_evenement_show', ['id' => $ev->getIdEv()]),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $sessionUser && !$isPast) {
            $entryCode    = random_int(100000, 999999);
            $confirmToken = bin2hex(random_bytes(32));
            $montant      = $participant->getNbrPlaces() * (float) $ev->getFraisInscription();

            $participant->setEvenement($ev);
            $participant->setIdUtilisateur($sessionUser->getId());
            $participant->setDateInscription(new \DateTime());
            $participant->setStatutParticipation('En attente');
            $participant->setEntryCode($entryCode);
            $participant->setConfirmation('pending');
            $participant->setMontantPayee((string) $montant);
            $participant->setConfirmToken($confirmToken);

            $em->persist($participant);
            $em->flush();

            try {
                $html = $this->renderView('emails/inscription_confirmation.html.twig', [
                    'nom'         => $participant->getNomParticipant(),
                    'evenement'   => $ev->getTitre(),
                    'date_debut'  => $ev->getDateDebut()?->format('d/m/Y H:i'),
                    'date_fin'    => $ev->getDateFin()?->format('d/m/Y H:i'),
                    'lieu'        => $ev->getLieu(),
                    'nbr_places'  => $participant->getNbrPlaces(),
                    'montant'     => $montant,
                    'entry_code'  => $entryCode,
                    'confirm_url' => $this->generateUrl('app_confirmer_inscription', ['token' => $confirmToken], UrlGeneratorInterface::ABSOLUTE_URL),
                    'url'         => $this->generateUrl('app_evenement_show', ['id' => $ev->getIdEv()], UrlGeneratorInterface::ABSOLUTE_URL),
                ]);
                $mailer->send((new Email())->from('noreply@agricore.tn')->to($participant->getEmail())->subject('✅ Confirmation — ' . $ev->getTitre())->html($html));
            } catch (\Exception) {}

            $this->addFlash('info', "Vérifiez votre email ({$participant->getEmail()}) et cliquez sur le lien de confirmation.");
            return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
        }

        return $this->render('front/evenements/show.html.twig', [
            'evenement'              => $ev,
            'placesRestantes'        => $placesRestantes,
            'dejaInscrit'            => $dejaInscrit,
            'onWaitlist'             => $onWaitlist,
            'participantConfirmation'=> $participantConfirmation,
            'isPast'                 => $isPast,
            'conflictingEvent'       => $conflictingEvent,
            'form'                   => $form->createView(),
            'showParticipationModal' => $form->isSubmitted() && !$form->isValid(),
            'participants'           => $this->buildParticipantList($ev, $em),
        ]);
    }
#[Route('/evenement/{id}/participer', name: 'app_participer', methods: ['POST'])]
public function participer(Evennementagricole $ev, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
{
    $sessionUser = $request->getSession()->get('user');
    if (!$sessionUser) return $this->redirectToRoute('front_login');

    if ($ev->getDateFin() < new \DateTime()) {
        $this->addFlash('error', 'Cet événement est terminé.');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    $totalReserved = $this->getTotalReservedPlaces($ev, $em);
    $dispo = max(0, $ev->getCapaciteMax() - $totalReserved);

    $participant = new Participants();
    $form = $this->createForm(ParticipantsType::class, $participant, ['max_places' => $dispo]);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entryCode    = random_int(100000, 999999);
        $confirmToken = bin2hex(random_bytes(32));
        $montant      = $participant->getNbrPlaces() * (float) $ev->getFraisInscription();

        $participant->setEvenement($ev);
        $participant->setIdUtilisateur($sessionUser->getId());
        $participant->setDateInscription(new \DateTime());
        $participant->setStatutParticipation('En attente');
        $participant->setEntryCode($entryCode);
        $participant->setConfirmation('pending');
        $participant->setMontantPayee((string) $montant);
        $participant->setConfirmToken($confirmToken);

        $em->persist($participant);
        $em->flush();

        try {
            $html = $this->renderView('emails/inscription_confirmation.html.twig', [
                'nom'         => $participant->getNomParticipant(),
                'evenement'   => $ev->getTitre(),
                'date_debut'  => $ev->getDateDebut()?->format('d/m/Y H:i'),
                'date_fin'    => $ev->getDateFin()?->format('d/m/Y H:i'),
                'lieu'        => $ev->getLieu(),
                'nbr_places'  => $participant->getNbrPlaces(),
                'montant'     => $montant,
                'entry_code'  => $entryCode,
                'confirm_url' => $this->generateUrl('app_confirmer_inscription', ['token' => $confirmToken], UrlGeneratorInterface::ABSOLUTE_URL),
                'url'         => $this->generateUrl('app_evenement_show', ['id' => $ev->getIdEv()], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            $mailer->send((new Email())->from('noreply@agricore.tn')->to($participant->getEmail())->subject('✅ Confirmation — ' . $ev->getTitre())->html($html));
        } catch (\Exception) {}

        $this->addFlash('info', "Vérifiez votre email ({$participant->getEmail()}) et cliquez sur le lien de confirmation.");
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    // Collect form errors and redirect back to modal
    $errors = [];
    foreach ($form->all() as $field) {
        foreach ($field->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }
    }

    return $this->redirectToRoute('app_evenement_show', [
        'id'     => $ev->getIdEv(),
        'modal'  => 1,
        'err'    => implode(' | ', $errors),
        'nom'    => $form->get('nom_participant')->getData(),
        'email'  => $form->get('email')->getData(),
        'places' => $form->get('nbr_places')->getData(),
    ]);
}
    #[Route('/inscription/confirmer/{token}', name: 'app_confirmer_inscription')]
    public function confirmerInscription(string $token, EntityManagerInterface $em): Response
    {
        $participant = $em->getRepository(Participants::class)->findOneBy(['confirm_token' => $token]);

        if (!$participant) {
            return new Response('<h2 style="font-family:sans-serif;text-align:center;margin-top:80px;color:#dc2626;">Lien invalide ou déjà utilisé.</h2>');
        }

        $participant->setConfirmation('confirmed');
        $participant->setConfirmToken(null);

        // Remove any waitlist entry for the same user/event
        $waitlistEntry = $em->getRepository(Participants::class)->findOneBy([
            'evenement'           => $participant->getEvenement(),
            'id_utilisateur'      => $participant->getIdUtilisateur(),
            'statut_participation'=> 'waitlist',
        ]);
        if ($waitlistEntry) $em->remove($waitlistEntry);

        $em->flush();

        $ev = $participant->getEvenement();
        $this->addFlash('success', '✅ Votre participation à "' . $ev->getTitre() . '" est confirmée !');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    #[Route('/evenement/{id}/waitlist', name: 'app_waitlist', methods: ['POST'])]
    public function joinWaitlist(Evennementagricole $ev, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) return $this->redirectToRoute('front_login');

        // Check not already registered or on waitlist
        $existing = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev, 'id_utilisateur' => $sessionUser->getId()
        ]);
        if ($existing) {
            $this->addFlash('info', 'Vous êtes déjà inscrit ou sur la liste d\'attente.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
        }

        $email = trim($request->request->get('email_waitlist', ''));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = 'noreply@waitlist.local'; // no email needed, notification is in-app
        }

        $p = new Participants();
        $p->setEvenement($ev);
        $p->setIdUtilisateur($sessionUser->getId());
        $p->setNomParticipant($sessionUser->getPrenom() . ' ' . $sessionUser->getNom());
        $p->setNbrPlaces(1);
        $p->setMontantPayee('0');
        $p->setDateInscription(new \DateTime());
        $p->setStatutParticipation('waitlist');
        $p->setEntryCode(0);
        $p->setConfirmation('pending');
        $p->setEmail($email);
        $em->persist($p);
        $em->flush();

        $this->addFlash('success', "Vous êtes sur la liste d'attente pour \"{$ev->getTitre()}\". Vous serez notifié par email si une place se libère.");
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    #[Route('/evenement/{id}/waitlist/annuler', name: 'app_waitlist_annuler', methods: ['POST'])]
    public function cancelWaitlist(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
    {
        $user = $request->getSession()->get('user');
        if (!$user) return $this->redirectToRoute('front_login');

        $p = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev, 'id_utilisateur' => $user->getId(), 'statut_participation' => 'waitlist'
        ]);
        if ($p) { $em->remove($p); $em->flush(); }

        $this->addFlash('success', 'Vous avez quitté la liste d\'attente.');
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }

    #[Route('/evenement/{id}/annuler', name: 'app_annuler_inscription', methods: ['POST'])]
    public function annulerInscription(Evennementagricole $ev, Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $user = $request->getSession()->get('user');
        if (!$user) return $this->redirectToRoute('front_login');

        $participant = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev, 'id_utilisateur' => $user->getId()
        ]);

        if ($participant) {
            $em->remove($participant);
            $em->flush();
            $this->addFlash('success', 'Inscription annulée.');
        }

        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
    }
}
