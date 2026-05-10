<?php

namespace App\Controller;

use App\Service\OAuthService;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use League\OAuth2\Client\Provider\GoogleUser;
use League\OAuth2\Client\Provider\FacebookUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OAuthController extends AbstractController
{
    private const PROVIDER_MAP = [
        'google'   => 'google',
        'facebook' => 'facebook_main',
        'github'   => 'github_main',
    ];

    private const SCOPES = [
        'google'   => ['openid', 'profile', 'email'],
        'facebook' => ['email', 'public_profile'],
        'github'   => ['read:user', 'user:email'],
    ];

    /**
     * Step 1: redirect user to OAuth provider's consent screen.
     */
    #[Route('/oauth/{provider}/connect', name: 'oauth_connect', methods: ['GET'], requirements: ['provider' => 'google|facebook|github'])]
    public function connect(string $provider, ClientRegistry $registry): Response
    {
        $clientKey = self::PROVIDER_MAP[$provider];
        return $registry
            ->getClient($clientKey)
            ->redirect(self::SCOPES[$provider], []);
    }

    /**
     * Step 2: callback — exchange code for token, fetch user, find or create.
     */
    #[Route('/oauth/{provider}/check', name: 'oauth_check', methods: ['GET'], requirements: ['provider' => 'google|facebook|github'])]
    public function check(
        string $provider,
        Request $request,
        ClientRegistry $registry,
        OAuthService $oauthService,
        HttpClientInterface $httpClient
    ): Response {
        $clientKey = self::PROVIDER_MAP[$provider];
        $client = $registry->getClient($clientKey);

        try {
            $accessToken = $client->getAccessToken();
            $providerUser = $client->fetchUserFromToken($accessToken);

            $info = $this->normalizeUser($provider, $providerUser, $accessToken->getToken(), $httpClient);

            $user = $oauthService->findOrCreateFromOAuth($provider, $info);

            if ($user->isBanned()) {
                $this->addFlash('error', 'Votre compte a ete banni. Contactez l\'administrateur.');
                return $this->redirectToRoute('front_login');
            }

            // Match the existing manual-login session pattern (UtilisateurController.php lines 81-93).
            // Also store user_id separately so controllers can recover from a flaky
            // session-unserialize without bouncing the user to the login page.
            $session = $request->getSession();
            $session->set('user', $user->prepareForSession());
            $session->set('user_id', $user->getId());

            $this->addFlash('success', 'Connexion reussie via ' . ucfirst($provider) . ' !');

            // First-time OAuth user gets a placeholder phone/address — nudge them
            if ((int) $user->getNumeroT() === 0 || $user->getAdresse() === 'A completer') {
                $this->addFlash('info', 'Bienvenue ! Pensez a completer votre profil (telephone, adresse).');
            }

            if ($user->getRole() === 0) {
                return $this->redirectToRoute('back_dashboard');
            }
            if ($user->getRole() === 2) {
                return $this->redirectToRoute('app_tech_home');
            }
            return $this->redirectToRoute('app_home');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la connexion ' . ucfirst($provider) . ' : ' . $e->getMessage());
            return $this->redirectToRoute('front_login');
        }
    }

    /**
     * Normalize provider-specific user objects into a flat array for OAuthService.
     *
     * @return array{email:string,name:?string,first_name:?string,last_name:?string,picture_url:?string}
     */
    private function normalizeUser(string $provider, object $providerUser, string $accessToken, HttpClientInterface $httpClient): array
    {
        return match ($provider) {
            'google' => $this->normalizeGoogle($providerUser),
            'facebook' => $this->normalizeFacebook($providerUser),
            'github' => $this->normalizeGithub($providerUser, $accessToken, $httpClient),
            default => throw new \InvalidArgumentException("Unsupported OAuth provider: $provider"),
        };
    }

    /**
     * @return array{email:string,name:?string,first_name:?string,last_name:?string,picture_url:?string}
     */
    private function normalizeGoogle(object $u): array
    {
        /** @var GoogleUser $u */
        return [
            'email'       => (string) $u->getEmail(),
            'name'        => $u->getName(),
            'first_name'  => $u->getFirstName(),
            'last_name'   => $u->getLastName(),
            'picture_url' => $u->getAvatar(),
        ];
    }

    /**
     * @return array{email:string,name:?string,first_name:?string,last_name:?string,picture_url:?string}
     */
    private function normalizeFacebook(object $u): array
    {
        /** @var FacebookUser $u */
        return [
            'email'       => (string) $u->getEmail(),
            'name'        => $u->getName(),
            'first_name'  => $u->getFirstName(),
            'last_name'   => $u->getLastName(),
            'picture_url' => $u->getPictureUrl(),
        ];
    }

    /**
     * @return array{email:string,name:?string,first_name:?string,last_name:?string,picture_url:?string}
     */
    private function normalizeGithub(object $u, string $accessToken, HttpClientInterface $httpClient): array
    {
        /** @var GithubResourceOwner $u */
        $email = $u->getEmail();

        // GitHub may not return email in profile if  — user has it privatefall back to /user/emails
        if (!$email) {
            try {
                $resp = $httpClient->request('GET', 'https://api.github.com/user/emails', [
                    'headers' => [
                        'Authorization' => 'token ' . $accessToken,
                        'Accept' => 'application/vnd.github+json',
                        'User-Agent' => 'utilisateur-symfony-app',
                    ],
                    'timeout' => 5,
                ]);
                $emails = $resp->toArray(false);
                foreach ($emails as $row) {
                    if (is_array($row) && !empty($row['primary']) && !empty($row['verified']) && isset($row['email']) && is_string($row['email'])) {
                        $email = $row['email'];
                        break;
                    }
                }
                if (!$email && isset($emails[0]) && is_array($emails[0]) && isset($emails[0]['email']) && is_string($emails[0]['email'])) {
                    $email = $emails[0]['email'];
                }
            } catch (\Throwable) {
                // swallow — handled below
            }
        }

        $raw = $u->toArray();
        $login = isset($raw['login']) && is_string($raw['login']) ? $raw['login'] : null;
        $avatarUrl = isset($raw['avatar_url']) && is_string($raw['avatar_url']) ? $raw['avatar_url'] : null;
        return [
            'email'       => $email !== null ? $email : '',
            'name'        => $u->getName() ?: $login,
            'first_name'  => null,
            'last_name'   => null,
            'picture_url' => $avatarUrl,
        ];
    }
}
