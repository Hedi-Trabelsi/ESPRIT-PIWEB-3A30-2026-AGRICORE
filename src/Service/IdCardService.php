<?php

namespace App\Service;

use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
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
            $imageBase64 = is_string($rawImage) ? $rawImage : null;
        }

        // Generate vCard QR code as PNG base64
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
            writer: new PngWriter(),
            data: $vcard,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 200,
            margin: 5,
            foregroundColor: new Color(15, 66, 41),
            backgroundColor: new Color(255, 255, 255),
        );
        $qrResult = $builder->build();

        $qrBase64 = base64_encode($qrResult->getString());

        $html = $this->twig->render('front/utilisateurs/_id_card.html.twig', [
            'user'        => $user,
            'role_label'  => self::ROLE_LABELS[$user->getRole()] ?? 'Utilisateur',
            'image_b64'   => $imageBase64,
            'qr_b64'      => $qrBase64,
            'issued_at'   => new \DateTimeImmutable(),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A6', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
