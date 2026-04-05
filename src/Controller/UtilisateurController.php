<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UtilisateurController extends AbstractController
{
    // ===================== LOGIN =====================
    #[Route('/utilisateurs/login', name: 'front_login', methods: ['GET', 'POST'])]
    public function login(Request $request, UserRepository $userRepo, HttpClientInterface $httpClient): Response
    {
        $hcaptchaKey = $this->getParameter('hcaptcha_site_key');

        if ($request->isMethod('GET')) {
            return $this->render('front/utilisateurs/login.html.twig', [
                'hcaptcha_site_key' => $hcaptchaKey,
            ]);
        }

        // Verify hCaptcha
        $captchaResponse = $request->request->get('h-captcha-response');
        if (!$captchaResponse) {
            $this->addFlash('error', 'Veuillez completer le captcha.');
            return $this->redirectToRoute('front_login');
        }

        $hcaptchaSecret = $this->getParameter('hcaptcha_secret');
        $verifyResponse = $httpClient->request('POST', 'https://api.hcaptcha.com/siteverify', [
            'body' => [
                'response' => $captchaResponse,
                'secret' => $hcaptchaSecret,
            ],
        ]);
        $verifyResult = $verifyResponse->toArray(false);
        if (!($verifyResult['success'] ?? false)) {
            $this->addFlash('error', 'Verification captcha echouee. Veuillez reessayer.');
            return $this->redirectToRoute('front_login');
        }

        $email = $request->request->get('email');
        $password = $request->request->get('password');

        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Aucun compte trouve avec cet email.');
            return $this->redirectToRoute('front_login');
        }

        // Support both hashed (new accounts) and plain text (old accounts)
        if (!password_verify($password, $user->getPassword()) && $user->getPassword() !== $password) {
            $this->addFlash('error', 'Mot de passe incorrect.');
            return $this->redirectToRoute('front_login');
        }

        if ($user->isBanned()) {
            $this->addFlash('error', 'Votre compte a ete banni. Contactez l\'administrateur.');
            return $this->redirectToRoute('front_login');
        }

        $session = $request->getSession();
        $user->prepareForSession();
        $session->set('user', $user);

        if ($user->getRole() === 0) {
            return $this->redirectToRoute('back_dashboard');
        }

        return $this->redirectToRoute('app_home');
    }

    // ===================== REGISTER =====================
    #[Route('/utilisateurs/register', name: 'front_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($userRepo->findOneBy(['email' => $user->getEmail()])) {
                $this->addFlash('error', 'Cet email est deja utilise.');
                return $this->render('front/utilisateurs/register.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $user->setBanned(false);

            // Hash the password
            $user->setPassword(password_hash($user->getPassword(), PASSWORD_BCRYPT));

            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $imageData = file_get_contents($imageFile->getPathname());
                $user->setImage(base64_encode($imageData));
                $user->setProfileComplete(true);
            } else {
                $user->setProfileComplete(false);
            }

            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte cree avec succes ! Connectez-vous.');
            return $this->redirectToRoute('front_login');
        }

        return $this->render('front/utilisateurs/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ===================== LOGOUT =====================
    #[Route('/utilisateurs/logout', name: 'utilisateur_logout')]
    public function logout(Request $request): Response
    {
        $request->getSession()->invalidate();
        return $this->redirectToRoute('front_login');
    }

    // ===================== PROFILE (frontend) =====================
    #[Route('/profil', name: 'app_profile')]
    public function profile(Request $request, UserRepository $userRepo): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }
        $user = $userRepo->find($sessionUser->getId());
        if ($user) {
            $user->prepareForSession();
            $request->getSession()->set('user', $user);
        }
        return $this->render('front/utilisateurs/profil.html.twig');
    }

    // ===================== EDIT PROFILE (frontend) =====================
    #[Route('/profil/modifier', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function editProfile(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setAdresse($request->request->get('adresse'));
            $user->setNumeroT((int) $request->request->get('numeroT'));
            $user->setGenre($request->request->get('genre'));

            $dateStr = $request->request->get('date');
            if ($dateStr) {
                $user->setDate(new \DateTime($dateStr));
            }

            $newPassword = $request->request->get('password');
            if ($newPassword && strlen($newPassword) > 0) {
                $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            }

            $imageFile = $request->files->get('image');
            if ($imageFile) {
                $imageData = file_get_contents($imageFile->getPathname());
                $user->setImage(base64_encode($imageData));
                $user->setProfileComplete(true);
            }

            $em->flush();

            $user->prepareForSession();
            $request->getSession()->set('user', $user);

            $this->addFlash('success', 'Profil mis a jour avec succes.');
            return $this->redirectToRoute('app_profile');
        }

        return $this->render('front/utilisateurs/edit_profil.html.twig', [
            'user' => $user,
        ]);
    }

    // ===================== BACKEND: LIST USERS =====================
    #[Route('/back/utilisateurs', name: 'back_utilisateurs')]
    public function backUtilisateurs(Request $request, UserRepository $userRepo): Response
    {
        $session = $request->getSession();
        $currentUser = $session->get('user');
        if (!$currentUser || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $users = $userRepo->findAll();
        $currentIds = array_map(fn($u) => $u->getId(), $users);

        // Use a file to persist seen IDs across sessions (survives logout)
        $seenFile = $this->getParameter('kernel.project_dir') . '/var/admin_seen_users.json';
        $seenIds = [];
        $newUsers = [];

        if (file_exists($seenFile)) {
            $seenIds = json_decode(file_get_contents($seenFile), true) ?: [];

            foreach ($users as $u) {
                if (!in_array($u->getId(), $seenIds)) {
                    $u->prepareForSession();
                    $newUsers[] = $u;
                }
            }
        }

        // Save current IDs to file
        file_put_contents($seenFile, json_encode($currentIds));

        return $this->render('back/utilisateurs/utilisateurs.html.twig', [
            'users' => $users,
            'new_users' => $newUsers,
        ]);
    }

    // ===================== BACKEND: PROFILE =====================
    #[Route('/back/profile', name: 'back_profile')]
    public function backProfile(Request $request): Response
    {
        $user = $request->getSession()->get('user');
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }
        return $this->render('back/utilisateurs/profile.html.twig');
    }

    // ===================== BACKEND: DELETE USER =====================
    #[Route('/back/utilisateurs/supprimer/{id}', name: 'back_utilisateur_supprimer')]
    public function deleteUser(int $id, EntityManagerInterface $em, UserRepository $userRepo, Request $request): Response
    {
        $currentUser = $request->getSession()->get('user');
        if (!$currentUser || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($id);
        if ($user) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprime avec succes.');
        }
        return $this->redirectToRoute('back_utilisateurs');
    }

    // ===================== BACKEND: BAN / UNBAN USER =====================
    #[Route('/back/utilisateurs/ban/{id}', name: 'back_utilisateur_ban')]
    public function banUser(int $id, EntityManagerInterface $em, UserRepository $userRepo, Request $request): Response
    {
        $currentUser = $request->getSession()->get('user');
        if (!$currentUser || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($id);
        if ($user) {
            $user->setBanned(!$user->isBanned());
            $em->flush();
            $status = $user->isBanned() ? 'banni' : 'debanni';
            $this->addFlash('success', 'Utilisateur ' . $status . ' avec succes.');
        }
        return $this->redirectToRoute('back_utilisateurs');
    }

    // ===================== BACKEND: MODIFY USER =====================
    #[Route('/back/utilisateurs/modifier/{id}', name: 'back_utilisateur_modifier', methods: ['GET', 'POST'])]
    public function modifyUser(int $id, Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $currentUser = $request->getSession()->get('user');
        if (!$currentUser || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        if ($request->isMethod('POST')) {
            $user->setNom($request->request->get('nom'));
            $user->setPrenom($request->request->get('prenom'));
            $user->setEmail($request->request->get('email'));
            $user->setAdresse($request->request->get('adresse'));
            $user->setNumeroT((int) $request->request->get('numeroT'));
            $user->setGenre($request->request->get('genre'));
            $user->setRole((int) $request->request->get('role'));

            $dateStr = $request->request->get('date');
            if ($dateStr) {
                $user->setDate(new \DateTime($dateStr));
            }

            $newPassword = $request->request->get('password');
            if ($newPassword && strlen($newPassword) > 0) {
                $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            }

            $em->flush();
            $this->addFlash('success', 'Utilisateur modifie avec succes.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        return $this->render('back/utilisateurs/modifier.html.twig', [
            'user' => $user,
        ]);
    }

    // ===================== FORGOT PASSWORD =====================
    #[Route('/utilisateurs/mot-de-passe-oublie', name: 'front_forgot_password')]
    public function forgotPassword(): Response
    {
        return $this->render('front/utilisateurs/forgot_password.html.twig');
    }
}
