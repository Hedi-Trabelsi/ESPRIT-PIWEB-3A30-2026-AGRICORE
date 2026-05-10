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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
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

        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');

        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            $this->addFlash('error', 'Aucun compte trouve avec cet email.');
            return $this->redirectToRoute('front_login');
        }

        // Support both hashed (new accounts) and plain text (old accounts)
        if (!password_verify($password, (string) $user->getPassword()) && $user->getPassword() !== $password) {
            $this->addFlash('error', 'Mot de passe incorrect.');
            return $this->redirectToRoute('front_login');
        }

        if ($user->isBanned()) {
            $this->addFlash('error', 'Votre compte a ete banni. Contactez l\'administrateur.');
            return $this->redirectToRoute('front_login');
        }

        $session = $request->getSession();
        $session->set('user', $user->prepareForSession());
        $session->set('user_id', $user->getId());

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
                $resp = $httpClient->request('GET', 'https://disify.com/api/email/' . urlencode((string) $user->getEmail()), [
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
            $user->setPassword(password_hash((string) $user->getPassword(), PASSWORD_BCRYPT));

            // Handle image upload
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $imageData = file_get_contents($imageFile->getPathname());
                if ($imageData !== false) {
                    $user->setImage(base64_encode($imageData));
                    $user->setProfileComplete(true);
                }
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
    public function profile(Request $request, UserRepository $userRepo, HttpClientInterface $httpClient, CacheInterface $cache): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser instanceof User) {
            return $this->redirectToRoute('front_login');
        }

        // Use the address from the session — no need to reload full User (with image BLOB) from DB
        $weatherData = null;
        $geoData = null;
        $apiError = null;
        $address = trim((string) $sessionUser->getAdresse());

        if ($address && strlen($address) > 1) {
            $locationiqKey = $this->getParameter('locationiq_api_key');
            $openweatherKey = $this->getParameter('openweather_api_key');

            // Build a list of address variants to try (handles common typos and fallbacks)
            $variants = [];
            $variants[] = $address;
            $variants[] = $this->normalizeAddress($address);
            $firstWord = trim(explode(',', explode(' ', $address)[0])[0]);
            if ($firstWord && strlen($firstWord) > 2) {
                $variants[] = $firstWord . ', Tunisie';
            }
            $variants[] = 'Ville de Tunis';
            $variants = array_values(array_unique(array_filter($variants)));

            // 1. Geocode — cached for 24h per address (avoids slow external calls on repeat visits)
            $geoCacheKey = 'geo_' . md5($address);
            $geoData = $cache->get($geoCacheKey, function (ItemInterface $item) use ($httpClient, $locationiqKey, $variants) {
                $item->expiresAfter(86400);
                foreach ($variants as $variant) {
                    try {
                        $geoResponse = $httpClient->request('GET', 'https://us1.locationiq.com/v1/search', [
                            'query' => [
                                'key' => $locationiqKey,
                                'q' => $variant,
                                'format' => 'json',
                                'limit' => 1,
                            ],
                            'timeout' => 3,
                        ]);
                        if ($geoResponse->getStatusCode() === 200) {
                            $geoResult = $geoResponse->toArray(false);
                            if (count($geoResult) > 0 && isset($geoResult[0]) && is_array($geoResult[0]) && isset($geoResult[0]['lat']) && is_scalar($geoResult[0]['lat'])) {
                                $first = $geoResult[0];
                                return [
                                    'lat' => (float) $first['lat'],
                                    'lon' => isset($first['lon']) && is_scalar($first['lon']) ? (float) $first['lon'] : 0.0,
                                    'display_name' => isset($first['display_name']) && is_string($first['display_name']) ? $first['display_name'] : $variant,
                                ];
                            }
                        }
                    } catch (\Throwable) {
                        // try next variant
                    }
                }
                // Don't cache failures for 24h — only 5 minutes so user can retry sooner
                $item->expiresAfter(300);
                return null;
            });

            if (!$geoData) {
                $apiError = 'Adresse "' . $address . '" introuvable. Veuillez la corriger depuis "Modifier profil".';
            }

            // 2. Weather — cached for 30min per location
            if ($geoData) {
                $weatherCacheKey = 'weather_' . md5($geoData['lat'] . ',' . $geoData['lon']);
                $weatherData = $cache->get($weatherCacheKey, function (ItemInterface $item) use ($httpClient, $openweatherKey, $geoData, $address) {
                    $item->expiresAfter(1800);
                    try {
                        $weatherResponse = $httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                            'query' => [
                                'lat' => $geoData['lat'],
                                'lon' => $geoData['lon'],
                                'appid' => $openweatherKey,
                                'units' => 'metric',
                                'lang' => 'fr',
                            ],
                            'timeout' => 3,
                        ]);
                        if ($weatherResponse->getStatusCode() === 200) {
                            $w = $weatherResponse->toArray(false);
                            $main = $w['main'] ?? null;
                            if (is_array($main)) {
                                $weatherList = $w['weather'] ?? [];
                                $first = is_array($weatherList) && isset($weatherList[0]) && is_array($weatherList[0]) ? $weatherList[0] : [];
                                $wind = $w['wind'] ?? [];
                                return [
                                    'temp' => round(is_numeric($main['temp'] ?? null) ? (float) $main['temp'] : 0),
                                    'desc' => ucfirst(isset($first['description']) && is_string($first['description']) ? $first['description'] : ''),
                                    'icon' => isset($first['icon']) && is_string($first['icon']) ? $first['icon'] : '01d',
                                    'humidity' => isset($main['humidity']) && is_numeric($main['humidity']) ? (int) $main['humidity'] : 0,
                                    'wind' => round(is_array($wind) && isset($wind['speed']) && is_numeric($wind['speed']) ? (float) $wind['speed'] : 0),
                                    'visibility' => round((isset($w['visibility']) && is_numeric($w['visibility']) ? (float) $w['visibility'] : 10000) / 1000, 1),
                                    'city' => isset($w['name']) && is_string($w['name']) ? $w['name'] : $address,
                                ];
                            }
                        }
                    } catch (\Throwable) {
                        // Don't cache failures for 30min, retry in 5min
                        $item->expiresAfter(300);
                    }
                    return null;
                });
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
        $user = $this->resolveCurrentUser($request, $userRepo);
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }
        $session = $request->getSession();

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if (is_string($newPassword) && strlen($newPassword) > 0) {
                $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            }

            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $imageData = file_get_contents($imageFile->getPathname());
                if ($imageData !== false) {
                    $user->setImage(base64_encode($imageData));
                    $user->setProfileComplete(true);
                }
            }

            $em->flush();

            $session->set('user', $user->prepareForSession());
            $session->set('user_id', $user->getId());

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
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $users = $userRepo->findAll();
        $currentIds = array_map(fn(User $u) => $u->getId(), $users);

        // Use a file to persist seen IDs across sessions (survives logout)
        $projectDir = $this->getParameter('kernel.project_dir');
        $seenFile = (is_string($projectDir) ? $projectDir : '') . '/var/admin_seen_users.json';
        $seenIds = [];
        $newUsers = [];

        if (file_exists($seenFile)) {
            $content = file_get_contents($seenFile);
            $decoded = $content !== false ? json_decode($content, true) : null;
            $seenIds = is_array($decoded) ? $decoded : [];

            foreach ($users as $u) {
                if (!in_array($u->getId(), $seenIds)) {
                    // Don't mutate the managed entity — use the stripped clone for the
                    // notif card list. The avatar is rendered via app_user_avatar anyway.
                    $newUsers[] = $u->prepareForSession();
                }
            }
        }

        // Save current IDs to file
        file_put_contents($seenFile, (string) json_encode($currentIds));

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
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
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
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
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
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        if ($request->isMethod('POST')) {
            $user->setNom((string) $request->request->get('nom', ''));
            $user->setPrenom((string) $request->request->get('prenom', ''));
            $user->setEmail((string) $request->request->get('email', ''));
            $user->setAdresse((string) $request->request->get('adresse', ''));
            $user->setNumeroT((int) $request->request->get('numeroT'));
            $user->setGenre((string) $request->request->get('genre', ''));
            $user->setRole((int) $request->request->get('role'));

            $dateStr = (string) $request->request->get('date', '');
            if ($dateStr !== '') {
                $user->setDate(new \DateTime($dateStr));
            }

            $newPassword = (string) $request->request->get('password', '');
            if (strlen($newPassword) > 0) {
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
            $url = 'https://disify.com/api/email/' . urlencode((string) $email);
            $json = file_get_contents($url, false, $ctx);
            if (is_string($json) && $json !== '') {
                $result = json_decode($json, true);

                if (is_array($result) && isset($result['format']) && $result['format'] === false) {
                    return new JsonResponse(['status' => 'invalid', 'message' => 'Format email invalide']);
                }
                if (is_array($result) && isset($result['disposable']) && $result['disposable'] === true) {
                    return new JsonResponse(['status' => 'disposable', 'message' => 'Email temporaire non accepte']);
                }
                if (is_array($result) && isset($result['dns']) && $result['dns'] === false) {
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
                $email = trim((string) $request->request->get('email', ''));
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
                $appSecret = $this->getParameter('app_secret');
                $raw = hash('sha256', $email . (is_string($appSecret) ? $appSecret : ''), true);
                $base32Secret = Base32::encodeUpper(substr($raw, 0, 20));
                if ($base32Secret === '' || $email === '') {
                    return $this->redirectToRoute('front_forgot_password');
                }

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
            if (!$userId || !is_string($secret) || $secret === '') {
                return $this->redirectToRoute('front_forgot_password');
            }

            $totp = TOTP::createFromSecret($secret);
            $totp->setIssuer('Agricore');
            $totp->setPeriod(30);
            $totp->setDigits(6);

            $user = $userRepo->find($userId);

            if ($request->isMethod('POST')) {
                $code = trim((string) $request->request->get('totp_code', ''));

                if ($code !== '' && $totp->verify($code, null, 1)) {
                    $session->set('fp_verified', true);
                    return $this->render('front/utilisateurs/forgot_password.html.twig', ['step' => 'reset']);
                }

                $this->addFlash('error', 'Code incorrect ou expire. Veuillez reessayer.');
            }

            $emailLabel = $user ? (string) $user->getEmail() : '';
            if ($emailLabel !== '') {
                $totp->setLabel($emailLabel);
            }

            return $this->render('front/utilisateurs/forgot_password.html.twig', [
                'step' => 'verify',
                'provisioningUri' => $totp->getProvisioningUri(),
                'userEmail' => $emailLabel,
            ]);
        }

        // === STEP 3: RESET PASSWORD ===
        if ($step === 'reset') {
            if (!$session->get('fp_verified')) {
                return $this->redirectToRoute('front_forgot_password');
            }

            if ($request->isMethod('POST')) {
                $password = (string) $request->request->get('password', '');
                $passwordConfirm = (string) $request->request->get('password_confirm', '');

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
        if (!$sessionUser instanceof User) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) < 2) {
            return new JsonResponse(['results' => []]);
        }

        // DQL projection: SELECT only the small scalar fields we need. No image BLOB loaded.
        // The template renders avatars via the app_user_avatar route, so no image data in JSON.
        $needle = '%' . mb_strtolower($q) . '%';
        $rows = $userRepo->createQueryBuilder('u')
            ->select('u.id, u.prenom, u.nom, u.email, u.role')
            ->where('LOWER(u.prenom) LIKE :n OR LOWER(u.nom) LIKE :n OR LOWER(u.email) LIKE :n')
            ->andWhere('u.id != :selfId')
            ->andWhere('(u.banned IS NULL OR u.banned = :notBanned)')
            ->setParameter('n', $needle)
            ->setParameter('selfId', $sessionUser->getId())
            ->setParameter('notBanned', false)
            ->setMaxResults(8)
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse(['results' => $rows]);
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
        if (!$user instanceof User || $user->isBanned()) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $roleLabel = match ($user->getRole()) {
            0 => 'Administrateur',
            1 => 'Agriculteur',
            2 => 'Technicien',
            default => 'Utilisateur',
        };

        $vcard = "BEGIN:VCARD\r\n"
            . "VERSION:3.0\r\n"
            . "N:" . $user->getNom() . ";" . $user->getPrenom() . "\r\n"
            . "FN:" . $user->getPrenom() . " " . $user->getNom() . "\r\n"
            . "EMAIL:" . $user->getEmail() . "\r\n"
            . "TEL:" . $user->getNumeroT() . "\r\n"
            . "ADR:;;" . $user->getAdresse() . "\r\n"
            . "ORG:Agricore\r\n"
            . "END:VCARD\r\n";

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
            'qr_png_b64' => $qrBase64,
        ]);
    }

    // ===================== QR CODE VCARD (Profile) =====================
    #[Route('/profil/qrcode', name: 'app_profile_qrcode')]
    public function profileQrCode(Request $request, UserRepository $userRepo): Response
    {
        $sessionUser = $request->getSession()->get('user');
        if (!$sessionUser instanceof User) {
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
        if (!$sessionUser instanceof User) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $messageRaw = $payload['message'] ?? '';
        $message = is_scalar($messageRaw) ? trim((string) $messageRaw) : '';
        if ($message === '') {
            return new JsonResponse(['error' => 'Empty message'], 400);
        }
        $message = mb_substr($message, 0, 500);

        // Keep only last 8 history entries to bound cost/latency
        $history = [];
        if (isset($payload['history']) && is_array($payload['history'])) {
            $raw = array_slice($payload['history'], -8);
            foreach ($raw as $h) {
                if (!is_array($h) || !isset($h['role'], $h['content']) || !is_scalar($h['content'])) {
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
        if (!is_string($apiKey) || $apiKey === '' || $apiKey === 'YOUR_GROQ_API_KEY') {
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
            $choices = $data['choices'] ?? [];
            $reply = null;
            if (is_array($choices) && isset($choices[0]) && is_array($choices[0])) {
                $msg = $choices[0]['message'] ?? null;
                if (is_array($msg) && isset($msg['content']) && is_string($msg['content'])) {
                    $reply = $msg['content'];
                }
            }
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
        if (!$sessionUser instanceof User) {
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
    public function exportUsersExcel(Request $request, UserRepository $userRepo): Response
    {
        $currentUser = $request->getSession()->get('user');
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
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
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $user = $userRepo->find($id);
        if (!$user) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        $subject = trim((string) $request->request->get('subject', ''));
        $message = trim((string) $request->request->get('message', ''));

        if ($subject === '' || $message === '') {
            $this->addFlash('error', 'Le sujet et le message sont obligatoires.');
            return $this->redirectToRoute('back_utilisateurs');
        }

        try {
            $htmlBody = $this->buildBrandedEmailHtml((string) $user->getPrenom(), $message);
            $email = (new Email())
                ->from('heditrabelsi412@gmail.com')
                ->to((string) $user->getEmail())
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
        if (!$currentUser instanceof User || $currentUser->getRole() !== 0) {
            return $this->redirectToRoute('front_login');
        }

        $subject = trim((string) $request->request->get('subject', ''));
        $message = trim((string) $request->request->get('message', ''));
        $targetRole = (string) $request->request->get('role', 'all');

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
                $htmlBody = $this->buildBrandedEmailHtml((string) $u->getPrenom(), $message);
                $email = (new Email())
                    ->from('heditrabelsi412@gmail.com')
                    ->to((string) $u->getEmail())
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
     * Stream the user's avatar so templates don't need to inline ~200-500 KB of base64
     * on every page. Returns a placeholder when the user has no image.
     *
     * Caching strategy: ETag based on a hash of the image bytes so the browser revalidates
     * (small 304 round trip) instead of returning a stale image after the user uploads a
     * new one. Cache-Control max-age is short for the same reason.
     */
    #[Route('/utilisateurs/avatar/{id}', name: 'app_user_avatar', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function avatar(int $id, Request $request, UserRepository $userRepo): Response
    {
        $user = $userRepo->find($id);
        $raw = $user !== null ? $user->getImage() : null;

        $binary = null;
        if (is_string($raw) && $raw !== '') {
            // Two storage formats observed in production:
            //  1. Base64-encoded string (newer code path uses base64_encode($imageData))
            //  2. Raw binary blob (legacy)
            $binary = mb_check_encoding($raw, 'ASCII')
                ? (base64_decode($raw, true) ?: null)
                : $raw;
        }

        if ($binary === null) {
            // 1x1 transparent PNG fallback so <img> tags don't break.
            $binary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkAAIAAAoAAv/lxKUAAAAASUVORK5CYII=');
        }

        // Detect content type from the magic bytes — Google avatars can be PNG or JPEG.
        $contentType = 'image/jpeg';
        if (str_starts_with($binary, "\x89PNG")) {
            $contentType = 'image/png';
        } elseif (str_starts_with($binary, 'GIF8')) {
            $contentType = 'image/gif';
        } elseif (str_starts_with($binary, 'RIFF') && str_contains(substr($binary, 0, 12), 'WEBP')) {
            $contentType = 'image/webp';
        }

        $response = new Response($binary);
        $response->headers->set('Content-Type', $contentType);
        // ETag-based revalidation: browsers send If-None-Match and we return 304 (empty
        // body) if unchanged. `no-cache` (despite the name) stores the response but
        // forces revalidation on every request — which means a freshly-uploaded avatar
        // is visible immediately, with no `max-age` window during which the browser
        // would silently keep showing the old image.
        $response->setEtag(md5($binary));
        $response->headers->set('Cache-Control', 'no-cache, private');
        $response->isNotModified($request);

        return $response;
    }

    /**
     * Resolve the currently logged-in User in a way that survives a flaky session
     * unserialize. PHP can occasionally produce a __PHP_Incomplete_Class for the
     * stored User object (especially when the User class signature evolves between
     * serialize/unserialize), which makes a strict `instanceof User` check fail and
     * the user gets bounced to the login page.
     *
     * Strategy: try the session.user.id first; fall back to a plain int session.user_id
     * key set alongside the User on every login. As long as ONE is valid, look up the
     * fresh User from the repository.
     *
     * Returns null only if both session keys are missing/invalid OR the user doesn't
     * exist in the database — in which case the caller should redirect to login.
     */
    private function resolveCurrentUser(Request $request, UserRepository $userRepo): ?User
    {
        $session = $request->getSession();

        $userId = null;
        $sessionUser = $session->get('user');
        if ($sessionUser instanceof User) {
            $maybeId = $sessionUser->getId();
            if (is_int($maybeId) && $maybeId > 0) {
                $userId = $maybeId;
            }
        }
        if ($userId === null) {
            $fallback = $session->get('user_id');
            if (is_int($fallback) && $fallback > 0) {
                $userId = $fallback;
            }
        }

        if ($userId === null) {
            return null;
        }

        return $userRepo->find($userId);
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
    /**
     * Generate a role + gender aware avatar using a real AI image generator
     * (Pollinations.ai, `turbo` model — ~2s response, generally not rate-limited
     * unlike `flux`). Falls back to DiceBear template avatars if the AI call fails.
     *
     * The prompt asks explicitly for an "agriculteur Tunisien" / "technicien" /
     * "professionnel" matching the user's role and gender, so the result actually
     * looks like the persona the user has in the app.
     */
    private function generateAiAvatar(User $user, HttpClientInterface $httpClient, ?int $seedOverride = null): ?string
    {
        $role     = $user->getRole();
        $genre    = mb_strtolower((string) $user->getGenre());
        $isFemale = str_contains($genre, 'fem');

        // Seed variation: stable per user, but `seedOverride` (the controller passes
        // time()) gives a fresh portrait every time the user clicks "Generate".
        $seedBase  = (string) ($user->getId() ?? random_int(1, 999999));
        $seedTwist = $seedOverride !== null ? (string) $seedOverride : (string) random_int(1, 999999);
        $seed      = abs(crc32($seedBase . '-' . $seedTwist . '-' . $genre));

        // Build a role-specific prompt. Each prompt is in French because the app is
        // French and the AI tends to render Tunisian/Mediterranean features more
        // accurately when the prompt is too. Style is "warm cartoon illustration"
        // so we get something friendly rather than a hyper-realistic photo.
        $person = $isFemale ? 'femme' : 'homme';

        // Front-load the role keyword — image models weight early tokens more.
        // Keep prompts short: Pollinations turbo is faster on simple prompts.
        $person = $isFemale ? 'woman' : 'man';
        $prompt = match ($role) {
            1 => sprintf(
                'Farmer portrait, %s farmer wearing a straw hat and denim overall, '
                . 'agriculture worker, holding a wheat stalk, sunny olive grove and wheat field background, '
                . 'cartoon style, friendly smiling face, centered head and shoulders, profile avatar',
                $person,
            ),
            2 => sprintf(
                'Agricultural technician portrait, %s wearing a blue work uniform and yellow safety helmet, '
                . 'holding a wrench and clipboard, green tractor in background, '
                . 'cartoon style, friendly smiling face, centered head and shoulders, profile avatar',
                $person,
            ),
            0 => sprintf(
                'Business professional portrait, %s wearing a formal suit, '
                . 'office background with green plants, '
                . 'cartoon style, friendly smiling face, centered head and shoulders, profile avatar',
                $person,
            ),
            default => sprintf(
                'Friendly %s cartoon avatar portrait, centered face, profile avatar',
                $person,
            ),
        };

        // 1) Try Pollinations turbo (real AI from prompt).
        // Short timeout (8s) so we fall back fast when Pollinations is rate-limiting,
        // instead of leaving the user staring at a spinner.
        $pollinationsUrl = 'https://image.pollinations.ai/prompt/' . rawurlencode($prompt)
            . '?' . http_build_query([
                'width'   => 400,
                'height'  => 400,
                'seed'    => $seed,
                'nologo'  => 'true',
                'model'   => 'turbo',
            ]);

        try {
            $response = $httpClient->request('GET', $pollinationsUrl, ['timeout' => 8]);
            if ($response->getStatusCode() === 200) {
                $content = $response->getContent(false);
                // Pollinations turbo actually returns JPEG (not PNG) and sometimes
                // returns a JSON error body with HTTP 200 when throttled. Accept any
                // real image format via magic-byte sniffing — the avatar route below
                // auto-detects content type the same way.
                if ($this->isImageBinary($content)) {
                    return base64_encode($content);
                }
            }
        } catch (\Throwable) {
            // fall through to DiceBear
        }

        // 2) Fallback: DiceBear (no AI, but role-themed and always available).
        return $this->generateDiceBearFallback($role ?? 1, $genre, $seed, $httpClient);
    }

    /**
     * Magic-byte sniff: is this binary string actually a recognized image format?
     * Used to reject Pollinations.ai's "Too Many Requests" JSON error bodies that
     * sometimes come back with HTTP 200.
     */
    private function isImageBinary(string $content): bool
    {
        if ($content === '' || strlen($content) < 8) {
            return false;
        }
        return str_starts_with($content, "\x89PNG")           // PNG
            || str_starts_with($content, "\xFF\xD8\xFF")      // JPEG
            || str_starts_with($content, 'GIF8')              // GIF
            || (str_starts_with($content, 'RIFF') && str_contains(substr($content, 8, 4), 'WEBP')); // WebP
    }

    private function generateDiceBearFallback(int $role, string $genre, int $seed, HttpClientInterface $httpClient): ?string
    {
        $commonParams = [
            'seed'            => (string) $seed . '-' . $genre,
            'size'            => 400,
            'backgroundType'  => 'gradientLinear',
            'backgroundColor' => 'b6e3f4,c0aede,d1d4f9,ffd5dc,ffdfbf',
        ];

        if ($role === 1) {
            // Farmer: lock in the farmer signals so it can't render as a corporate
            // cartoon — denim overall + hat (always), warm earth-tone skin + beard +
            // smiling mouth + happy eyes. Greenish backdrop to hint at the field.
            $style  = 'avataaars';
            $params = $commonParams + [
                'clothing'               => 'overall',
                'top'                    => 'hat',
                'facialHair'             => 'beardLight,beardMedium,beardMajestic,moustacheMagnum',
                'facialHairProbability'  => 80,
                'accessoriesProbability' => 0,
                'mouth'                  => 'smile,twinkle',
                'eyes'                   => 'happy,wink,default',
                'skinColor'              => 'd08b5b,edb98a,ae5d29,fd9841',
                'backgroundColor'        => 'a7ffc4,b1e2ff,ffdfbf,d1d4f9',
            ];
        } elseif ($role === 2) {
            $style  = 'bottts';
            $params = $commonParams;
        } else {
            $style  = 'personas';
            $params = $commonParams;
        }

        $url = sprintf('https://api.dicebear.com/9.x/%s/png?%s', $style, http_build_query($params));

        try {
            $response = $httpClient->request('GET', $url, ['timeout' => 8]);
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
        $user = $this->resolveCurrentUser($request, $userRepo);
        if (!$user) {
            return $this->redirectToRoute('front_login');
        }

        // Use time-based seed so regeneration produces a different avatar each time
        $aiAvatar = $this->generateAiAvatar($user, $httpClient, time());
        if ($aiAvatar) {
            $user->setImage($aiAvatar);
            $user->setProfileComplete(true);
            $em->flush();
            $session = $request->getSession();
            $session->set('user', $user->prepareForSession());
            $session->set('user_id', $user->getId());
            $this->addFlash('success', 'Avatar IA genere avec succes !');
        } else {
            $this->addFlash('error', 'Impossible de generer l\'avatar pour le moment. Reessayez dans un instant.');
        }

        return $this->redirectToRoute('app_profile_edit');
    }
}
