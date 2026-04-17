<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Centralizes OAuth user lookup/creation logic.
 *
 * Strategy: link OAuth identities to existing users by email — uses ONLY the existing
 * 13 columns of the user table (no schema changes).
 */
class OAuthService
{
    public function __construct(
        private readonly UserRepository $userRepo,
        private readonly EntityManagerInterface $em,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param string $provider 'google' | 'facebook' | 'github'
     * @param array{email:string,name:?string,first_name:?string,last_name:?string,picture_url:?string} $info
     */
    public function findOrCreateFromOAuth(string $provider, array $info): User
    {
        $email = strtolower(trim($info['email'] ?? ''));
        if ($email === '') {
            throw new \RuntimeException("Le fournisseur OAuth $provider n'a pas renvoye d'email.");
        }

        // 1) Returning user — match by email
        $existing = $this->userRepo->findOneBy(['email' => $email]);
        if ($existing) {
            return $existing;
        }

        // 2) New user — create with sensible defaults using ONLY existing columns
        $user = new User();
        $user->setEmail($email);

        // Name handling: prefer first/last from provider, else split full name, else fallback
        $first = trim($info['first_name'] ?? '');
        $last  = trim($info['last_name'] ?? '');
        if ($first === '' || $last === '') {
            $full = trim($info['name'] ?? '');
            if ($full !== '') {
                $parts = preg_split('/\s+/', $full, 2);
                $first = $first ?: ($parts[0] ?? '');
                $last  = $last  ?: ($parts[1] ?? $parts[0] ?? '');
            }
        }
        if ($first === '') {
            $first = strstr($email, '@', true) ?: 'Utilisateur';
        }
        if ($last === '') {
            $last = ucfirst($provider);
        }

        // Min length 2 enforced by entity
        $user->setPrenom(substr($first, 0, 50) ?: 'Utilisateur');
        $user->setNom(substr($last, 0, 50) ?: ucfirst($provider));

        // Random password (column NOT NULL) — user can reset via existing 2FA forgot-password flow
        $randomPassword = bin2hex(random_bytes(16));
        $user->setPassword(password_hash($randomPassword, PASSWORD_BCRYPT));

        // Placeholders for required fields
        $user->setNumeroT(0);
        $user->setAdresse('A completer');
        $user->setGenre('Homme');
        $user->setRole(1); // Farmer (default)
        $user->setBanned(false);

        // Avatar download
        $avatarUrl = $info['picture_url'] ?? null;
        $imageData = $avatarUrl ? $this->downloadAvatar($avatarUrl) : null;
        if ($imageData) {
            $user->setImage(base64_encode($imageData));
            $user->setProfileComplete(true);
        } else {
            $user->setProfileComplete(false);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function downloadAvatar(string $url): ?string
    {
        try {
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
                'max_redirects' => 3,
            ]);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $content = $response->getContent(false);
            return $content !== '' ? $content : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
