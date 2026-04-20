<?php

namespace App\Service;

use App\Entity\Reservation;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private readonly Environment $twig
    ) {
    }

    public function genererFacture(Reservation $reservation): string
    {
        $html = $this->twig->render('pdf/facture.html.twig', [
            'reservation' => $reservation,
            'date_generation' => new \DateTime(),
            'numero_facture' => 'FAC-' . str_pad((string) $reservation->getId(), 6, '0', STR_PAD_LEFT),
        ]);

        return $this->genererPdf($html);
    }

    public function genererContrat(Reservation $reservation): string
    {
        $html = $this->twig->render('pdf/contrat.html.twig', [
            'reservation' => $reservation,
            'date_generation' => new \DateTime(),
            'numero_contrat' => 'CTR-' . str_pad((string) $reservation->getId(), 6, '0', STR_PAD_LEFT),
        ]);

        return $this->genererPdf($html);
    }

    private function genererPdf(string $html): string
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
