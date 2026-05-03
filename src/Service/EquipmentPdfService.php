<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Equipement;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class EquipmentPdfService
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    /**
     * @param Equipement[] $equipements
     */
    public function renderCataloguePdf(array $equipements, array $stats): string
    {
        $html = $this->twig->render('back/achat_equipement/pdf.html.twig', [
            'equipements' => $equipements,
            'stats' => $stats,
            'generatedAt' => new \DateTimeImmutable('+1 hour'),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function renderOrderPdf(Commande $commande): string
    {
        $html = $this->twig->render('front/achat_equipement/order_pdf.html.twig', [
            'commande' => $commande,
            'generatedAt' => new \DateTimeImmutable('+1 hour'),
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
