<?php

namespace App\Controller;

use App\Entity\Messages;
use App\Entity\Participants;
use App\Entity\Evennementagricole;
use App\Repository\EvennementagricoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    #[Route('/evenement/{id}/chat', name: 'app_event_chat')]
    public function chat(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
    {
        $user = $request->getSession()->get('user');

        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        // Vérifier que l'utilisateur est inscrit
        $participant = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev,
            'id_utilisateur' => $user->getId()
        ]);

        if (!$participant) {
            $this->addFlash('error', 'Vous devez être inscrit à cet événement pour accéder au chat.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
        }

        $messages = $em->getRepository(Messages::class)
            ->createQueryBuilder('m')
            ->where('m.event_id = :eid')
            ->setParameter('eid', $ev->getIdEv())
            ->orderBy('m.timestamp', 'ASC')
            ->getQuery()
            ->getResult();

        // Build sender name map from participants
        $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev]);
        $names = [];
        foreach ($participants as $p) {
            $names[$p->getIdUtilisateur()] = $p->getNomParticipant();
        }

        return $this->render('front/evenements/chat.html.twig', [
            'evenement' => $ev,
            'messages' => $messages,
            'currentUser' => $user,
            'names' => $names,
        ]);
    }

    #[Route('/evenement/{id}/chat/send', name: 'app_event_chat_send', methods: ['POST'])]
    public function send(Evennementagricole $ev, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $request->getSession()->get('user');

        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        // Vérifier inscription
        $participant = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev,
            'id_utilisateur' => $user->getId()
        ]);

        if (!$participant) {
            return new JsonResponse(['error' => 'Non inscrit'], 403);
        }

        $content = trim($request->request->get('content', ''));
        if (empty($content)) {
            return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $msg = new Messages();
        $msg->setSender_id($user->getId());
        $msg->setReceiver_id(0); // 0 = message de groupe
        $msg->setContent($content);
        $msg->setTimestamp(new \DateTime());
        $msg->setEventId($ev->getIdEv());

        $em->persist($msg);
        $em->flush();

        return new JsonResponse([
            'id' => $msg->getId(),
            'sender_id' => $msg->getSender_id(),
            'sender_name' => $user->getPrenom() . ' ' . $user->getNom(),
            'content' => $msg->getContent(),
            'timestamp' => $msg->getTimestamp()->format('H:i'),
        ]);
    }

    #[Route('/evenement/{id}/chat/messages', name: 'app_event_chat_poll', methods: ['GET'])]
    public function poll(Evennementagricole $ev, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $request->getSession()->get('user');
        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $since = (int) $request->query->get('since', 0);

        $qb = $em->getRepository(Messages::class)
            ->createQueryBuilder('m')
            ->where('m.event_id = :eid')
            ->setParameter('eid', $ev->getIdEv())
            ->orderBy('m.timestamp', 'ASC');

        if ($since > 0) {
            $qb->andWhere('m.id > :since')->setParameter('since', $since);
        }

        $messages = $qb->getQuery()->getResult();

        // Récupérer les noms des participants pour affichage
        $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev]);
        $names = [];
        foreach ($participants as $p) {
            $names[$p->getIdUtilisateur()] = $p->getNomParticipant();
        }

        $data = [];
        foreach ($messages as $msg) {
            $data[] = [
                'id' => $msg->getId(),
                'sender_id' => $msg->getSender_id(),
                'sender_name' => $names[$msg->getSender_id()] ?? 'Participant',
                'content' => $msg->getContent(),
                'timestamp' => $msg->getTimestamp()->format('H:i'),
                'is_mine' => $msg->getSender_id() === $user->getId(),
            ];
        }

        return new JsonResponse($data);
    }
}
