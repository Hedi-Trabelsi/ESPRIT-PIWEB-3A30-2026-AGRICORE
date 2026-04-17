<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\IdCardService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\SvgWriter;
use OTPHP\TOTP;
use ParagonIE\ConstantTime\Base32;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

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

        if ($user->getRole() === 2) {
            return $this->redirectToRoute('app_tech_home');
        }

        return $this->redirectToRoute('app_home');
    }

    // ===================== REGISTER =====================
    #[Route('/utilisateurs/register', name: 'front_register', methods: ['GET', 'POST'])]
    public function register(Request $request, EntityManagerInterface $em, UserRepository $userRepo, HttpClientInterface $httpClient): Response
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

            // Validate email with Disify API (free, no key needed) - soft check with short timeout
            try {
                $resp = $httpClient->request('GET', 'https://disify.com/api/email/' . urlencode($user->getEmail()), [
                    'timeout' => 3,
                    'max_duration' => 4,
                ]);
                if ($resp->getStatusCode() === 200) {
                    $emailResult = $resp->toArray(false);
                    if (isset($emailResult['disposable']) && $emailResult['disposable'] === true) {
                        $this->addFlash('error', 'Les adresses email temporaires ne sont pas acceptees.');
                        return $this->render('front/utilisateurs/register.html.twig', [
                            'form' => $form->createView(),
                        ]);
                    }
                    if (isset($emailResult['dns']) && $emailResult['dns'] === false) {
                        $this->addFlash('error', 'Le domaine de cet email n\'existe pas.');
                        return $this->render('front/utilisateurs/register.html.twig', [
                            'form' => $form->createView(),
                        ]);
                    }
                }
            } catch (\Throwable) {
                // API timeout or failure - continue registration anyway
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
    public function profile(Request $request, UserRepository $userRepo, HttpClientInterface $httpClient): Response
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

        $weatherData = null;
        $geoData = null;
        $apiError = null;
        $address = $user ? trim($user->getAdresse()) : '';

        if ($address && strlen($address) > 1) {
            $locationiqKey = $this->getParameter('locationiq_api_key');
            $openweatherKey = $this->getParameter('openweather_api_key');

            // Build a list of address variants to try (handles common typos and fallbacks)
            $variants = [];
            $variants[] = $address;
            $variants[] = $this->normalizeAddress($address);
            // Fallback: try just the first word (likely the city)
            $firstWord = trim(explode(',', explode(' ', $address)[0] ?? '')[0] ?? '');
            if ($firstWord && strlen($firstWord) > 2) {
                $variants[] = $firstWord . ', Tunisie';
            }
            // Ultimate fallback: default to Tunis
            $variants[] = 'Ville de Tunis';
            $variants = array_values(array_unique(array_filter($variants)));

            // 1. Geocode with LocationIQ via HttpClient — try each variant
            foreach ($variants as $variant) {
                try {
                    $geoResponse = $httpClient->request('GET', 'https://us1.locationiq.com/v1/search', [
                        'query' => [
                            'key' => $locationiqKey,
                            'q' => $variant,
                            'format' => 'json',
                            'limit' => 1,
                        ],
                        'timeout' => 8,
                    ]);
                    if ($geoResponse->getStatusCode() === 200) {
                        $geoResult = $geoResponse->toArray(false);
                        if (is_array($geoResult) && count($geoResult) > 0 && isset($geoResult[0]['lat'])) {
                            $geoData = [
                                'lat' => (float) $geoResult[0]['lat'],
                                'lon' => (float) $geoResult[0]['lon'],
                                'display_name' => $geoResult[0]['display_name'] ?? $variant,
                            ];
                            break; // success
                        }
                    }
                } catch (\Throwable) {
                    // try next variant
                }
            }

            if (!$geoData) {
                $apiError = 'Adresse "' . $address . '" introuvable. Veuillez la corriger depuis "Modifier profil".';
            }

            // 2. Weather via OpenWeather (only if geocoding succeeded)
            if ($geoData) {
                try {
                    $weatherResponse = $httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                        'query' => [
                            'lat' => $geoData['lat'],
                            'lon' => $geoData['lon'],
                            'appid' => $openweatherKey,
                            'units' => 'metric',
                            'lang' => 'fr',
                        ],
                        'timeout' => 8,
                    ]);
                    if ($weatherResponse->getStatusCode() === 200) {
                        $w = $weatherResponse->toArray(false);
                        if (isset($w['main'])) {
                            $weatherData = [
                                'temp' => round($w['main']['temp']),
                                'desc' => ucfirst($w['weather'][0]['description'] ?? ''),
                                'icon' => $w['weather'][0]['icon'] ?? '01d',
                                'humidity' => $w['main']['humidity'] ?? 0,
                                'wind' => round($w['wind']['speed'] ?? 0),
                                'visibility' => round(($w['visibility'] ?? 10000) / 1000, 1),
                                'city' => $w['name'] ?? $address,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    // Keep geo data, just no weather
                }
            }
        }

        return $this->render('front/utilisateurs/profil.html.twig', [
            'weatherData' => $weatherData,
            'geoData' => $geoData,
            'apiError' => $apiError,
        ]);
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

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if ($newPassword && strlen($newPassword) > 0) {
                $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            }

            $imageFile = $form->get('imageFile')->getData();
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
            'form' => $form->createView(),
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

    // ===================== EMAIL VALIDATION API (Abstract API) =====================
    #[Route('/api/validate-email', name: 'api_validate_email', methods: ['POST'])]
    public function validateEmail(Request $request): JsonResponse
    {
        $email = $request->request->get('email', '');
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['status' => 'invalid', 'message' => 'Format email invalide']);
        }

        // Disify API (free, no key needed)
        try {
            $ctx = stream_context_create(['http' => ['timeout' => 5]]);
            $url = 'https://disify.com/api/email/' . urlencode($email);
            $json = file_get_contents($url, false, $ctx);
            if ($json) {
                $result = json_decode($json, true);

                if (isset($result['format']) && $result['format'] === false) {
                    return new JsonResponse(['status' => 'invalid', 'message' => 'Format email invalide']);
                }
                if (isset($result['disposable']) && $result['disposable'] === true) {
                    return new JsonResponse(['status' => 'disposable', 'message' => 'Email temporaire non accepte']);
                }
                if (isset($result['dns']) && $result['dns'] === false) {
                    return new JsonResponse(['status' => 'invalid', 'message' => 'Le domaine n\'existe pas']);
                }
                return new JsonResponse(['status' => 'valid', 'message' => 'Email valide']);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['status' => 'error', 'message' => 'Erreur de verification']);
        }

        return new JsonResponse(['status' => 'error', 'message' => 'Erreur']);
    }

    // ===================== FORGOT PASSWORD (Google Authenticator TOTP) =====================
    #[Route('/utilisateurs/mot-de-passe-oublie', name: 'front_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(Request $request, UserRepository $userRepo, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $step = $request->request->get('step', $request->query->get('step', 'email'));

        // === STEP 1: EMAIL ===
        if ($step === 'email') {
            if ($request->isMethod('POST')) {
                $email = trim($request->request->get('email', ''));
                $user = $userRepo->findOneBy(['email' => $email]);

                if (!$user) {
                    $this->addFlash('error', 'Aucun compte trouve avec cet email.');
                    return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'email']);
                }

                if ($user->isBanned()) {
                    $this->addFlash('error', 'Ce compte est banni. Contactez l\'administrateur.');
                    return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'email']);
                }

                // Derive deterministic TOTP secret from email + APP_SECRET
                $raw = hash('sha256', $email . $this->getParameter('app_secret'), true);
                $base32Secret = Base32::encodeUpper(substr($raw, 0, 20));

                $totp = TOTP::createFromSecret($base32Secret);
                $totp->setLabel($email);
                $totp->setIssuer('Agricore');
                $totp->setPeriod(30);
                $totp->setDigits(6);

                $session->set('fp_user_id', $user->getId());
                $session->set('fp_secret', $base32Secret);
                $session->remove('fp_verified');

                return $this->render('front/utilisateurs/forgot_password.html.twig', [
                    'step' => 'verify',
                    'provisioningUri' => $totp->getProvisioningUri(),
                    'userEmail' => $email,
                ]);
            }
            return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'email']);
        }

        // === STEP 2: VERIFY TOTP CODE ===
        if ($step === 'verify') {
            $userId = $session->get('fp_user_id');
            $secret = $session->get('fp_secret');
            if (!$userId || !$secret) {
                return $this->redirectToRoute('front_forgot_password');
            }

            $totp = TOTP::createFromSecret($secret);
            $totp->setIssuer('Agricore');
            $totp->setPeriod(30);
            $totp->setDigits(6);

            $user = $userRepo->find($userId);

            if ($request->isMethod('POST')) {
                $code = trim($request->request->get('totp_code', ''));

                if ($totp->verify($code, null, 1)) {
                    $session->set('fp_verified', true);
                    return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'reset']);
                }

                $this->addFlash('error', 'Code incorrect ou expire. Veuillez reessayer.');
            }

            $totp->setLabel($user ? $user->getEmail() : '');

            return $this->render('front/utilisateurs/forgot_password.html.twig', [
                'step' => 'verify',
                'provisioningUri' => $totp->getProvisioningUri(),
                'userEmail' => $user ? $user->getEmail() : '',
            ]);
        }

        // === STEP 3: RESET PASSWORD ===
        if ($step === 'reset') {
            if (!$session->get('fp_verified')) {
                return $this->redirectToRoute('front_forgot_password');
            }

            if ($request->isMethod('POST')) {
                $password = $request->request->get('password', '');
                $passwordConfirm = $request->request->get('password_confirm', '');

                $errors = [];
                if ($password !== $passwordConfirm) {
                    $errors[] = 'Les mots de passe ne correspondent pas.';
                }
                if (strlen($password) < 6) {
                    $errors[] = 'Le mot de passe doit contenir au moins 6 caracteres.';
                }
                if (!preg_match('/[a-z]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir une lettre minuscule.';
                }
                if (!preg_match('/[A-Z]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir une lettre majuscule.';
                }
                if (!preg_match('/[0-9]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir un chiffre.';
                }

                if (!empty($errors)) {
                    foreach ($errors as $err) {
                        $this->addFlash('error', $err);
                    }
                    return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'reset']);
                }

                $user = $userRepo->find($session->get('fp_user_id'));
                if (!$user) {
                    $this->addFlash('error', 'Utilisateur introuvable.');
                    return $this->redirectToRoute('front_forgot_password');
                }

                $user->setPassword(password_hash($password, PASSWORD_BCRYPT));
                $em->flush();

                $session->remove('fp_user_id');
                $session->remove('fp_secret');
                $session->remove('fp_verified');

                $this->addFlash('success', 'Mot de passe reinitialise avec succes ! Connectez-vous avec votre nouveau mot de passe.');
                return $this->redirectToRoute('front_login');
            }

            return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'reset']);
        }

        return $this->redirectToRoute('front_forgot_password');
    }

    // ===================== SEARCH USERS (Friend finder) =====================
    #[Route('/api/search-users', name: 'api_search_users', methods: ['GET'])]
    public function apiSearchUsers(Request $request, UserRepository $userRepo): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            return new JsonResponse(['results' => []]);
        }

        $all = $userRepo->findAll();
        $matches = [];
        $needle = mb_strtolower($q);

        foreach ($all as $u) {
            if ($u->getId() === $sessionUser->getId()) continue; // skip self
            if ($u->isBanned()) continue;                         // skip banned
            $haystack = mb_strtolower($u->getPrenom() . ' ' . $u->getNom() . ' ' . $u->getEmail());
            if (str_contains($haystack, $needle)) {
                $matches[] = [
                    'id'     => $u->getId(),
                    'prenom' => $u->getPrenom(),
                    'nom'    => $u->getNom(),
                    'email'  => $u->getEmail(),
                    'role'   => $u->getRole(),
                    'image'  => $this->safeImageBase64($u->getImage()),
                ];
            }
            if (count($matches) >= 8) break;
        }

        return new JsonResponse(['results' => $matches]);
    }

    // ===================== FRIEND CONTACT (Full contact + vCard QR) =====================
    #[Route('/api/user-contact/{id}', name: 'api_user_contact', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function apiUserContact(int $id, Request $request, UserRepository $userRepo): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $user = $userRepo->find($id);
        if (!$user || $user->isBanned()) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $roleLabel = match ($user->getRole()) {
            0 => 'Administrateur',
            1 => 'Agriculteur',
            2 => 'Technicien',
            default => 'Utilisateur',
        };

        // Build vCard
        $vcard = "BEGIN:VCARD\r\n"
            . "VERSION:3.0\r\n"
            . "N:" . $user->getNom() . ";" . $user->getPrenom() . "\r\n"
            . "FN:" . $user->getPrenom() . " " . $user->getNom() . "\r\n"
            . "EMAIL:" . $user->getEmail() . "\r\n"
            . "TEL:" . $user->getNumeroT() . "\r\n"
            . "ADR:;;" . $user->getAdresse() . "\r\n"
            . "ORG:Agricore\r\n"
            . "END:VCARD\r\n";

        // Generate QR as PNG base64
        $builder = new Builder(
            writer: new \Endroid\QrCode\Writer\PngWriter(),
            data: $vcard,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 8,
            foregroundColor: new Color(15, 66, 41),
            backgroundColor: new Color(255, 255, 255),
        );
        $qrBase64 = base64_encode($builder->build()->getString());

        return new JsonResponse([
            'id'         => $user->getId(),
            'prenom'     => $user->getPrenom(),
            'nom'        => $user->getNom(),
            'email'      => $user->getEmail(),
            'telephone'  => $user->getNumeroT(),
            'adresse'    => $user->getAdresse(),
            'genre'      => $user->getGenre(),
            'role'       => $user->getRole(),
            'role_label' => $roleLabel,
            'image'      => $this->safeImageBase64($user->getImage()),
            'qr_png_b64' => $qrBase64,
        ]);
    }

    // ===================== QR CODE VCARD (Profile) =====================
    #[Route('/profil/qrcode', name: 'app_profile_qrcode')]
    public function profileQrCode(Request $request, UserRepository $userRepo): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        // Build vCard data
        $vcard = "BEGIN:VCARD\r\n";
        $vcard .= "VERSION:3.0\r\n";
        $vcard .= "N:" . $user->getNom() . ";" . $user->getPrenom() . "\r\n";
        $vcard .= "FN:" . $user->getPrenom() . " " . $user->getNom() . "\r\n";
        $vcard .= "EMAIL:" . $user->getEmail() . "\r\n";
        $vcard .= "TEL:" . $user->getNumeroT() . "\r\n";
        $vcard .= "ADR:;;" . $user->getAdresse() . "\r\n";
        $vcard .= "ORG:Agricore\r\n";
        $vcard .= "END:VCARD\r\n";

        $builder = new Builder(
            writer: new SvgWriter(),
            data: $vcard,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            foregroundColor: new Color(15, 66, 41),
            backgroundColor: new Color(255, 255, 255),
        );

        $result = $builder->build();

        return new Response($result->getString(), 200, [
            'Content-Type' => 'image/svg+xml',
        ]);
    }

    // ===================== AI CHATBOT (Groq + Llama) =====================
    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function apiChat(Request $request, HttpClientInterface $httpClient): JsonResponse
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $message = trim((string) ($payload['message'] ?? ''));
        if ($message === '') {
            return new JsonResponse(['error' => 'Empty message'], 400);
        }
        $message = mb_substr($message, 0, 500);

        // Keep only last 8 history entries to bound cost/latency
        $history = [];
        if (isset($payload['history']) && is_array($payload['history'])) {
            $raw = array_slice($payload['history'], -8);
            foreach ($raw as $h) {
                if (!isset($h['role'], $h['content'])) {
                    continue;
                }
                $role = $h['role'] === 'user' ? 'user' : 'assistant';
                $history[] = [
                    'role'    => $role,
                    'content' => mb_substr((string) $h['content'], 0, 1000),
                ];
            }
        }

        $roleLabel = match ($sessionUser->getRole()) {
            0 => 'Administrateur',
            1 => 'Agriculteur',
            2 => 'Technicien',
            default => 'Utilisateur',
        };

        $systemPrompt = "Tu es l'assistant IA d'Agricore, une plateforme de gestion agricole. "
            . "Tu reponds toujours en francais, de maniere brève et concrète (max 3 phrases). "
            . "Tu t'adresses a " . $sessionUser->getPrenom() . " " . $sessionUser->getNom()
            . " (role: " . $roleLabel . "). "
            . "Fonctionnalites d'Agricore: inscription classique ou via Google/Facebook/GitHub (OAuth), "
            . "profil modifiable avec photo, carte d'identite PDF a telecharger depuis /profil, "
            . "QR code vCard pour partager ses contacts, reinitialisation de mot de passe via 2FA Google Authenticator, "
            . "export Excel des utilisateurs (admin), widget meteo local et geolocalisation sur le profil, "
            . "gestion d'equipements, depenses, ventes, animaux, maintenance. "
            . "Si la question sort totalement du cadre d'Agricore, invite poliment l'utilisateur a la reformuler.";

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $history,
            [['role' => 'user', 'content' => $message]],
        );

        $apiKey = $this->getParameter('groq_api_key');
        if ($apiKey === '' || $apiKey === 'YOUR_GROQ_API_KEY') {
            return new JsonResponse([
                'reply' => "Desole, l'assistant IA n'est pas encore configure (cle API Groq manquante).",
            ]);
        }

        try {
            $response = $httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'timeout' => 15,
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.3-70b-versatile',
                    'messages'    => $messages,
                    'max_tokens'  => 300,
                    'temperature' => 0.7,
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                return new JsonResponse([
                    'reply' => "Desole, je suis indisponible pour le moment. Reessayez dans un instant.",
                ]);
            }

            $data = $response->toArray(false);
            $reply = $data['choices'][0]['message']['content'] ?? null;
            if (!is_string($reply) || $reply === '') {
                $reply = "Je n'ai pas pu formuler de reponse. Pouvez-vous reformuler votre question ?";
            }

            return new JsonResponse(['reply' => trim($reply)]);
        } catch (\Throwable) {
            return new JsonResponse([
                'reply' => "Desole, je suis indisponible pour le moment. Reessayez dans un instant.",
            ]);
        }
    }

    // ===================== PDF ID CARD (Profile) =====================
    #[Route('/profil/id-card', name: 'app_profile_id_card', methods: ['GET'])]
    public function profileIdCard(Request $request, UserRepository $userRepo, IdCardService $idCardService): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        $pdfBinary = $idCardService->generate($user);

        return new Response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="carte_' . $user->getId() . '.pdf"',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    // ===================== EXPORT USERS TO EXCEL (Admin) =====================
    #[Route('/back/utilisateurs/export-excel', name: 'back_utilisateurs_export_excel')]
    public function exportUsersExcel(Request $request, UserRepository $userRepo): StreamedResponse
    {
        $currentUser = $request->getSession()->get('user');
        if (!$currentUser || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $users = $userRepo->findAll();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Utilisateurs Agricore');

        // Header row
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $headers = ['ID', 'Nom', 'Prenom', 'Email', 'Telephone', 'Adresse', 'Genre', 'Role', 'Statut', 'Profil complet'];
        foreach ($headers as $i => $header) {
            $sheet->getCell($columns[$i] . '1')->setValue($header);
        }
        $headerStyle = $sheet->getStyle('A1:J1');
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('348E38');

        // Data rows
        $row = 2;
        foreach ($users as $user) {
            $roleLabel = match ($user->getRole()) {
                0 => 'Administrateur',
                1 => 'Agriculteur',
                2 => 'Technicien',
                default => 'Inconnu',
            };
            $statusLabel = $user->isBanned() ? 'Banni' : 'Actif';
            $profileLabel = $user->isProfileComplete() ? 'Oui' : 'Non';

            $sheet->getCell('A' . $row)->setValue($user->getId());
            $sheet->getCell('B' . $row)->setValue($user->getNom());
            $sheet->getCell('C' . $row)->setValue($user->getPrenom());
            $sheet->getCell('D' . $row)->setValue($user->getEmail());
            $sheet->getCell('E' . $row)->setValue($user->getNumeroT());
            $sheet->getCell('F' . $row)->setValue($user->getAdresse());
            $sheet->getCell('G' . $row)->setValue($user->getGenre());
            $sheet->getCell('H' . $row)->setValue($roleLabel);
            $sheet->getCell('I' . $row)->setValue($statusLabel);
            $sheet->getCell('J' . $row)->setValue($profileLabel);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="utilisateurs_agricore.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    // ===================== SEND EMAIL TO SINGLE USER (Admin) =====================
    #[Route('/back/utilisateurs/email/{id}', name: 'back_utilisateur_email', methods: ['POST'])]
    public function sendEmailToUser(int $id, Request $request, UserRepository $userRepo, MailerInterface $mailer): Response
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

        $subject = trim($request->request->get('subject', ''));
        $message = trim($request->request->get('message', ''));

        if ($subject === '' || $message === '') {
            $this->addFlash('error', 'Le sujet et le message sont obligatoires.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        try {
            $htmlBody = $this->buildBrandedEmailHtml($user->getPrenom(), $message);
            $email = (new Email())
                ->from('heditrabelsi412@gmail.com')
                ->to($user->getEmail())
                ->subject($subject)
                ->html($htmlBody);
            $mailer->send($email);
            $this->addFlash('success', 'Email envoye avec succes a ' . $user->getPrenom() . ' ' . $user->getNom() . '.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi : ' . $e->getMessage());
        }

        return $this->redirectToRoute('back_utilisateurs');
    }

    // ===================== SEND EMAIL TO USER GROUP (Admin) =====================
    #[Route('/back/utilisateurs/email-group', name: 'back_utilisateur_email_group', methods: ['POST'])]
    public function sendEmailToGroup(Request $request, UserRepository $userRepo, MailerInterface $mailer): Response
    {
        $currentUser = $request->getSession()->get('user');
        if (!$currentUser || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $subject = trim($request->request->get('subject', ''));
        $message = trim($request->request->get('message', ''));
        $targetRole = $request->request->get('role', 'all');

        if ($subject === '' || $message === '') {
            $this->addFlash('error', 'Le sujet et le message sont obligatoires.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        $allUsers = $userRepo->findAll();
        $recipients = [];
        foreach ($allUsers as $u) {
            if ($u->getRole() === 0) continue; // skip admins
            if ($u->isBanned()) continue;       // skip banned
            if ($targetRole !== 'all' && $u->getRole() !== (int) $targetRole) continue;
            $recipients[] = $u;
        }

        if (empty($recipients)) {
            $this->addFlash('error', 'Aucun destinataire trouve pour ce groupe.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        $sent = 0;
        foreach ($recipients as $u) {
            try {
                $htmlBody = $this->buildBrandedEmailHtml($u->getPrenom(), $message);
                $email = (new Email())
                    ->from('heditrabelsi412@gmail.com')
                    ->to($u->getEmail())
                    ->subject($subject)
                    ->html($htmlBody);
                $mailer->send($email);
                $sent++;
            } catch (\Throwable) {
                // continue to next recipient
            }
        }

        $this->addFlash('success', 'Email envoye avec succes a ' . $sent . ' utilisateur(s).');
        return $this->redirectToRoute('back_utilisateurs');
    }

    /**
     * Ensure image data is safe for JSON output. Images are expected to be base64 text,
     * but legacy rows may hold raw binary BLOBs which would break json_encode.
     */
    private function safeImageBase64(?string $image): ?string
    {
        if ($image === null || $image === '') {
            return null;
        }
        // Base64 is pure ASCII. If it's not ASCII, it's raw binary — encode it.
        if (!mb_check_encoding($image, 'ASCII')) {
            return base64_encode($image);
        }
        return $image;
    }

    /**
     * Normalize common Tunisian address typos to improve geocoding success.
     */
    private function normalizeAddress(string $address): string
    {
        $a = trim(mb_strtolower($address));
        // Common misspellings -> correct names
        $fixes = [
            'manzel bou zelfa' => 'Menzel Bouzelfa',
            'manzel bouzelfa'  => 'Menzel Bouzelfa',
            'menzel bou zelfa' => 'Menzel Bouzelfa',
            'menzel bouzelfa'  => 'Menzel Bouzelfa',
            'tunis'            => 'Ville de Tunis',
        ];
        foreach ($fixes as $bad => $good) {
            if (str_contains($a, $bad)) {
                return $good;
            }
        }
        // Return title-cased version as last resort
        return mb_convert_case($address, MB_CASE_TITLE, 'UTF-8');
    }

    private function buildBrandedEmailHtml(string $prenom, string $messageBody): string
    {
        $escapedPrenom = htmlspecialchars($prenom, ENT_QUOTES, 'UTF-8');
        $escapedBody = nl2br(htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8'));

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head><meta charset="utf-8"></head>
        <body style="margin:0;padding:0;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;background:#f4f6f7;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f7;padding:32px 0;">
                <tr><td align="center">
                    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 16px rgba(0,0,0,0.08);">
                        <tr>
                            <td style="background:linear-gradient(135deg,#0F4229,#348E38);padding:28px 32px;text-align:center;">
                                <h1 style="margin:0;color:#ffffff;font-size:24px;letter-spacing:2px;">AGRICORE</h1>
                                <p style="margin:4px 0 0;color:rgba(255,255,255,0.8);font-size:12px;">Plateforme de gestion agricole</p>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding:32px;">
                                <p style="font-size:16px;color:#222;margin:0 0 16px;">Bonjour <strong>{$escapedPrenom}</strong>,</p>
                                <div style="font-size:14px;color:#444;line-height:1.7;margin-bottom:24px;">
                                    {$escapedBody}
                                </div>
                                <hr style="border:none;border-top:1px solid #e5e5e5;margin:24px 0;">
                                <p style="font-size:12px;color:#999;margin:0;">Cordialement,<br><strong>L'equipe Agricore</strong></p>
                            </td>
                        </tr>
                        <tr>
                            <td style="background:#f8faf8;padding:16px 32px;text-align:center;">
                                <p style="font-size:11px;color:#aaa;margin:0;">Cet email a ete envoye par l'administration Agricore. Veuillez ne pas repondre directement a ce message.</p>
                            </td>
                        </tr>
                    </table>
                </td></tr>
            </table>
        </body>
        </html>
        HTML;
    }

    // ===================== AI AVATAR HELPER =====================
    private function generateAiAvatar(User $user, HttpClientInterface $httpClient, ?int $seedOverride = null): ?string
    {
        $role = $user->getRole();
        $genre = mb_strtolower((string) $user->getGenre());
        $isFemale = str_contains($genre, 'fem') || $genre === 'femme';
        $gender = $isFemale ? 'female' : 'male';

        $prompt = match ($role) {
            1 => "cute cartoon portrait of a {$gender} farmer, green farm background, flat illustration style, friendly smile, high quality avatar",
            2 => "cute cartoon portrait of a {$gender} technician wearing work uniform, flat illustration style, professional, high quality avatar",
            0 => "cute cartoon portrait of a {$gender} business professional, green theme, flat illustration style, high quality avatar",
            default => "cute cartoon portrait avatar, flat illustration style, high quality",
        };

        $seed = $seedOverride ?? ($user->getId() ?? random_int(1, 999999));
        $url = 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt)
             . '?width=400&height=400&seed=' . $seed
             . '&nologo=true&model=flux';

        try {
            $response = $httpClient->request('GET', $url, ['timeout' => 20]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $content = $response->getContent(false);
            if ($content === '') {
                return null;
            }
            return base64_encode($content);
        } catch (\Throwable) {
            return null;
        }
    }

    // ===================== AI AVATAR REGENERATE (Profile) =====================
    #[Route('/profil/ai-avatar', name: 'app_profile_ai_avatar', methods: ['GET', 'POST'])]
    public function generateAvatarAction(Request $request, UserRepository $userRepo, EntityManagerInterface $em, HttpClientInterface $httpClient): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($sessionUser->getId());
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        // Use time-based seed so regeneration produces a different avatar each time
        $aiAvatar = $this->generateAiAvatar($user, $httpClient, time());
        if ($aiAvatar) {
            $user->setImage($aiAvatar);
            $user->setProfileComplete(true);
            $em->flush();
            $user->prepareForSession();
            $request->getSession()->set('user', $user);
            $this->addFlash('success', 'Avatar IA genere avec succes !');
        } else {
            $this->addFlash('error', 'Impossible de generer l\'avatar pour le moment. Reessayez dans un instant.');
        }

        return $this->redirectToRoute('app_profile_edit');
    }
}
