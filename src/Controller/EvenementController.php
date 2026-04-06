<?php

namespace App\Controller;

use App\Entity\Evennementagricole;
use App\Entity\Participants;
use App\Repository\EvennementagricoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EvenementController extends AbstractController
{
    // ===========================
    // 🔵 LISTE DES ÉVÉNEMENTS
    // ===========================
    #[Route('/evenements', name: 'app_evenement')]
    public function index(
        EvennementagricoleRepository $repo,
        EntityManagerInterface $em
    ): Response {

        $evenements = $repo->findAll();
        $data = [];
        $now = new \DateTime();

        foreach ($evenements as $ev) {

            // ================= STATUS =================
            if ($ev->getDate_fin() < $now) {
                $status = 'HISTORIQUE';
            } elseif ($ev->getDate_debut() > $now) {
                $status = 'COMING';
            } else {
                $status = 'EN_COURS';
            }

            // ================= COUNT PARTICIPANTS =================
            $places = $em->getRepository(Participants::class)
                ->count(['evenement' => $ev]);

            $placesRestantes = $ev->getCapacite_max() - $places;

            $data[] = [
                'evenement' => $ev,
                'status' => $status,
                'placesRestantes' => $placesRestantes
            ];
        }

        return $this->render('front/evenements/evenements.html.twig', [
            'evenements' => $data
        ]);
    }

    // ===========================
    // 🟢 DÉTAIL ÉVÉNEMENT
    // ===========================
    #[Route('/evenement/{id}', name: 'app_evenement_show')]
    public function show(
        Evennementagricole $ev,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        $places = $em->getRepository(Participants::class)
            ->count(['evenement' => $ev]);

        $placesRestantes = $ev->getCapacite_max() - $places;

        $dejaInscrit = false;
        $sessionUser = $request->getSession()->get('user');
        if ($sessionUser) {
            $existing = $em->getRepository(Participants::class)->findOneBy([
                'evenement' => $ev,
                'id_utilisateur' => $sessionUser->getId()
            ]);
            $dejaInscrit = ($existing !== null);
        }

        return $this->render('front/evenements/show.html.twig', [
            'evenement' => $ev,
            'placesRestantes' => $placesRestantes,
            'dejaInscrit' => $dejaInscrit
        ]);
    }

    // ===========================
    // 🔴 ANNULER UNE INSCRIPTION
    // ===========================
    #[Route('/evenement/{id}/annuler', name: 'app_annuler_inscription', methods: ['POST'])]
    public function annulerInscription(
        Evennementagricole $ev,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $participant = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev,
            'id_utilisateur' => $sessionUser->getId()
        ]);

        if ($participant) {
            $em->remove($participant);
            $em->flush();
            $this->addFlash('success', 'Votre inscription a été annulée avec succès.');
        }

        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getId_ev()]);
    }

    // ===========================
    // 🟣 PARTICIPER À UN ÉVÉNEMENT
    // ===========================
    #[Route('/evenement/{id}/participer', name: 'app_participer', methods: ['POST'])]
    public function participer(
        Evennementagricole $ev,
        Request $request,
        EntityManagerInterface $em
    ): Response {

        // 1. Vérification de la session
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            $this->addFlash('error', 'Vous devez être connecté pour participer.');
            return $this->redirectToRoute('front_login');
        }

        // 2. Récupération des données du formulaire
        $nom = trim($request->request->get('nom_participant', ''));
        $nbrPlaces = (int) $request->request->get('nbr_places', 1);

        // 3. Logique de secours pour le nom
        if (empty($nom)) {
            // On suppose que l'objet user en session possède getPrenom() et getNom()
            $nom = $sessionUser->getPrenom() . ' ' . $sessionUser->getNom();
        }

        // 4. Validation du nombre de places
        if ($nbrPlaces < 1) {
            $nbrPlaces = 1;
        }

        // 5. CALCUL DU MONTANT PAYÉ (Côté Serveur pour la sécurité)
        $prixUnitaire = (float) $ev->getFrais_inscription();
        $montantTotal = $nbrPlaces * $prixUnitaire;

        // 6. Création de l'entité Participant
        $participant = new Participants();
        $participant->setEvenement($ev);
        $participant->setId_utilisateur($sessionUser->getId());
        $participant->setNom_participant($nom);
        $participant->setNbr_places($nbrPlaces);
        $participant->setStatut_participation("En attente");
        $participant->setEntry_code(random_int(100000, 999999));
        $participant->setDate_inscription(new \DateTime());
        
        // FIX : On enregistre le montant calculé et non "0"
        $participant->setMontant_payee((string)$montantTotal); 
        
        $participant->setConfirmation("pending");

        // 7. Persistance
        $em->persist($participant);
        $em->flush();

        $this->addFlash('success', 'Inscription réussie ! Total : ' . $montantTotal . ' DT. Code d\'entrée : ' . $participant->getEntry_code());

        // Assurez-vous que le getter de l'ID correspond à votre entité (getId_ev ou getIdEv)
        return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getId_ev()]);
    }
}