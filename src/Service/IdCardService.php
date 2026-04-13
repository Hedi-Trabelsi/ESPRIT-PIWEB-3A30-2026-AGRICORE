<?php

namespace App\Service;

use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Generates a printable PDF "carte d'identite" for a user.
 * Reuses Dompdf (no API key needed). Renders an HTML/Twig card and returns PDF binary.
 */
class IdCardService
{
    private const ROLE_LABELS = [
        0 => 'Administrateur',
        1 => 'Agriculteur',
        2 => 'Technicien',
    ];

    public function __construct(private readonly Environment $twig)
    {
    }

    public function generate(User $user): string
    {
        $imageBase64 = null;
        $rawImage = $user->getImage();
        if ($rawImage) {
            // The image column stores already-base64-encoded bytes (cf. UtilisateurController register)
            $imageBase64 = is_string($rawImage) ? $rawImage : null;
        }

        $html = $this->twig->render('front/utilisateurs/_id_card.html.twig', [
            'user'        => $user,
            'role_label'  => self::ROLE_LABELS[$user->getRole()] ?? 'Utilisateur',
            'image_b64'   => $imageBase64,
            'issued_at'   => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        // A6 landscape ~ 105mm x 148mm — credit-card-ish proportions
        $dompdf->setPaper('A6', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
