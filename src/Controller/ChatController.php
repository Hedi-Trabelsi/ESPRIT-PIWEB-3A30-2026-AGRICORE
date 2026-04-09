<?php

namespace App\Controller;

use App\Entity\Messages;
use App\Entity\Participants;
use App\Entity\Evennementagricole;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatController extends AbstractController
{
    const ADMIN_SENDER_ID = 0;

    private function buildAvatarMap(array $participants, EntityManagerInterface $em): array
    {
        $avatars = [];
        foreach ($participants as $p) {
            $uid = $p->getIdUtilisateur();
            if (isset($avatars[$uid])) continue;
            $user = $em->getRepository(User::class)->find($uid);
            if ($user && $user->getImage()) {
                $raw = $user->getImage();
                if (is_resource($raw)) $raw = stream_get_contents($raw);
                $avatars[$uid] = 'data:image/jpeg;base64,' . base64_encode($raw);
            } else {
                $avatars[$uid] = null;
            }
        }
        return $avatars;
    }

    private function userAvatar(mixed $user): ?string
    {
        if (!$user) return null;
        $raw = $user->getImage();
        if (is_resource($raw)) $raw = stream_get_contents($raw);
        return $raw ? 'data:image/jpeg;base64,' . base64_encode($raw) : null;
    }

    #[Route('/evenement/{id}/chat', name: 'app_event_chat')]
    public function chat(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
    {
        $user = $request->getSession()->get('user');
        if (!$user) return $this->redirectToRoute('front_login');

        $participant = $em->getRepository(Participants::class)->findOneBy([
            'evenement' => $ev, 'id_utilisateur' => $user->getId()
        ]);
        if (!$participant) {
            $this->addFlash('error', 'Vous devez être inscrit à cet événement pour accéder au chat.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $ev->getIdEv()]);
        }

        $messages     = $em->getRepository(Messages::class)
            ->createQueryBuilder('m')
            ->where('m.event_id = :eid')->setParameter('eid', $ev->getIdEv())
            ->orderBy('m.timestamp', 'ASC')->getQuery()->getResult();

        $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev]);
        $names = [];
        foreach ($participants as $p) $names[$p->getIdUtilisateur()] = $p->getNomParticipant();

        $avatars = $this->buildAvatarMap($participants, $em);
        // Also include current user in the map
        $avatars[$user->getId()] = $this->userAvatar($user);

        return $this->render('front/evenements/chat.html.twig', [
            'evenement'   => $ev,
            'messages'    => $messages,
            'currentUser' => $user,
            'names'       => $names,
            'avatars'     => $avatars,
            'myAvatar'    => $this->userAvatar($user),
            'isAdmin'     => false,
        ]);
    }

    #[Route('/back/evenements/{id}/chat', name: 'back_event_chat')]
    public function adminChat(Evennementagricole $ev, Request $request, EntityManagerInterface $em): Response
    {
        $admin = $request->getSession()->get('user');
        if (!$admin) return $this->redirectToRoute('back_login');

        $messages     = $em->getRepository(Messages::class)
            ->createQueryBuilder('m')
            ->where('m.event_id = :eid')->setParameter('eid', $ev->getIdEv())
            ->orderBy('m.timestamp', 'ASC')->getQuery()->getResult();

        $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev]);
        $names = [];
        foreach ($participants as $p) $names[$p->getIdUtilisateur()] = $p->getNomParticipant();

        $avatars = $this->buildAvatarMap($participants, $em);
        // Also include admin in the map
        $avatars[$admin->getId()] = $this->userAvatar($admin);

        return $this->render('front/evenements/chat.html.twig', [
            'evenement'   => $ev,
            'messages'    => $messages,
            'currentUser' => $admin,
            'names'       => $names,
            'avatars'     => $avatars,
            'myAvatar'    => $this->userAvatar($admin),
            'isAdmin'     => true,
        ]);
    }

    #[Route('/evenement/{id}/chat/send', name: 'app_event_chat_send', methods: ['POST'])]
    public function send(Evennementagricole $ev, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $request->getSession()->get('user');
        if (!$user) return new JsonResponse(['error' => 'Non connecté'], 401);

        $isAdmin = (bool) $request->request->get('is_admin', false);

        if (!$isAdmin) {
            $participant = $em->getRepository(Participants::class)->findOneBy([
                'evenement' => $ev, 'id_utilisateur' => $user->getId()
            ]);
            if (!$participant) return new JsonResponse(['error' => 'Non inscrit'], 403);
        }

        $audioFile = $request->files->get('audio');
        if ($audioFile) {
            $audioData = base64_encode(file_get_contents($audioFile->getPathname()));
            $mimeType  = $audioFile->getMimeType() ?: 'audio/webm';
            $content   = '[AUDIO_B64]:data:' . $mimeType . ';base64,' . $audioData;
        } else {
            $content = trim($request->request->get('content', ''));
            if (empty($content)) return new JsonResponse(['error' => 'Message vide'], 400);
        }

        $msg = new Messages();
        $msg->setSender_id($isAdmin ? self::ADMIN_SENDER_ID : $user->getId());
        $msg->setReceiver_id(0);
        $msg->setContent($content);
        $msg->setTimestamp(new \DateTime());
        $msg->setEventId($ev->getIdEv());

        $em->persist($msg);
        $em->flush();

        return new JsonResponse([
            'id'            => $msg->getId(),
            'sender_id'     => $msg->getSender_id(),
            'sender_name'   => $isAdmin ? '👑 Admin' : $user->getPrenom() . ' ' . $user->getNom(),
            'sender_avatar' => $isAdmin ? null : $this->userAvatar($user),
            'content'       => $msg->getContent(),
            'timestamp'     => $msg->getTimestamp()->format('H:i'),
            'is_admin'      => $isAdmin,
        ]);
    }

    #[Route('/evenement/{id}/chat/messages', name: 'app_event_chat_poll', methods: ['GET'])]
    public function poll(Evennementagricole $ev, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $request->getSession()->get('user');
        if (!$user) return new JsonResponse(['error' => 'Non connecté'], 401);

        $since = (int) $request->query->get('since', 0);

        $qb = $em->getRepository(Messages::class)
            ->createQueryBuilder('m')
            ->where('m.event_id = :eid')->setParameter('eid', $ev->getIdEv())
            ->orderBy('m.timestamp', 'ASC');
        if ($since > 0) $qb->andWhere('m.id > :since')->setParameter('since', $since);

        $messages     = $qb->getQuery()->getResult();
        $participants = $em->getRepository(Participants::class)->findBy(['evenement' => $ev]);

        $names   = [];
        foreach ($participants as $p) $names[$p->getIdUtilisateur()] = $p->getNomParticipant();
        $avatars = $this->buildAvatarMap($participants, $em);
        $avatars[$user->getId()] = $this->userAvatar($user);

        $data = [];
        foreach ($messages as $msg) {
            $isAdminMsg = $msg->getSender_id() === self::ADMIN_SENDER_ID;
            $data[] = [
                'id'            => $msg->getId(),
                'sender_id'     => $msg->getSender_id(),
                'sender_name'   => $isAdminMsg ? '👑 Admin' : ($names[$msg->getSender_id()] ?? 'Participant'),
                'sender_avatar' => $isAdminMsg ? null : ($avatars[$msg->getSender_id()] ?? null),
                'content'       => $msg->getContent(),
                'timestamp'     => $msg->getTimestamp()->format('H:i'),
                'is_mine'       => !$isAdminMsg && $msg->getSender_id() === $user->getId(),
                'is_admin'      => $isAdminMsg,
            ];
        }

        return new JsonResponse($data);
    }
}
