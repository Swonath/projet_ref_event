<?php

namespace App\Controller\CentreCommercial;

use App\Entity\CentreCommercial;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/centre/paiements', name: 'centre_paiements_')]
#[IsGranted('ROLE_CENTRE')]
class PaiementController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        PaiementRepository $paiementRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $filtre = $request->query->get('filtre', 'tous');

        // Récupérer les paiements via les réservations
        $qb = $paiementRepo->createQueryBuilder('p')
            ->join('p.reservation', 'r')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->setParameter('centre', $centre)
            ->orderBy('p.datePaiement', 'DESC');

        // Appliquer le filtre
        if ($filtre !== 'tous') {
            $qb->andWhere('p.statut = :statut')
               ->setParameter('statut', $filtre);
        }

        $paiements = $qb->getQuery()->getResult();

        // Calculer les stats
        $stats = [
            'ca_total' => 0,
            'commission_totale' => 0,
            'net_a_recevoir' => 0,
            'en_attente' => 0,
            'remboursements' => 0,
        ];

        foreach ($paiements as $paiement) {
            $reservation = $paiement->getReservation();
            $stats['ca_total'] += $reservation->getMontantLocation();
            $stats['commission_totale'] += $reservation->getMontantCommission() ?? 0;

            if ($paiement->getStatut() === 'en_attente') {
                $stats['en_attente'] += $paiement->getMontant();
            }

            if ($paiement->getMontantRembourse()) {
                $stats['remboursements'] += $paiement->getMontantRembourse();
            }
        }

        $stats['net_a_recevoir'] = $stats['ca_total'] - $stats['commission_totale'];

        return $this->render('centre_commercial/paiements.html.twig', [
            'centre' => $centre,
            'paiements' => $paiements,
            'stats' => $stats,
            'filtre' => $filtre,
        ]);
    }
}
