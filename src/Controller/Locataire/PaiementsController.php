<?php

namespace App\Controller\Locataire;

use App\Entity\Locataire;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/locataire/paiements', name: 'locataire_paiements_')]
#[IsGranted('ROLE_LOCATAIRE')]
class PaiementsController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        PaiementRepository $paiementRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        $filtre = $request->query->get('statut', 'tous');

        // Récupérer tous les paiements des réservations du locataire
        $qb = $paiementRepo->createQueryBuilder('p')
            ->join('p.reservation', 'r')
            ->where('r.locataire = :locataire')
            ->setParameter('locataire', $locataire)
            ->orderBy('p.datePaiement', 'DESC');

        // Appliquer le filtre par statut
        if ($filtre !== 'tous') {
            $qb->andWhere('p.statut = :statut')
               ->setParameter('statut', $filtre);
        }

        $paiements = $qb->getQuery()->getResult();

        // Calculer les statistiques
        $statsQuery = $paiementRepo->createQueryBuilder('p')
            ->select('p.statut, COUNT(p.id) as count, SUM(p.montant) as total')
            ->join('p.reservation', 'r')
            ->where('r.locataire = :locataire')
            ->setParameter('locataire', $locataire)
            ->groupBy('p.statut')
            ->getQuery()
            ->getResult();

        $stats = [
            'total_paiements' => 0,
            'montant_total' => 0,
            'accepte' => ['count' => 0, 'montant' => 0],
            'en_attente' => ['count' => 0, 'montant' => 0],
            'rembourse' => ['count' => 0, 'montant' => 0],
            'refuse' => ['count' => 0, 'montant' => 0],
        ];

        foreach ($statsQuery as $stat) {
            $statut = $stat['statut'];
            if (isset($stats[$statut])) {
                $stats[$statut] = [
                    'count' => (int) $stat['count'],
                    'montant' => (float) $stat['total'],
                ];
            }
            $stats['total_paiements'] += (int) $stat['count'];
            $stats['montant_total'] += (float) $stat['total'];
        }

        // Calculer le total dépensé (paiements acceptés)
        $totalDepense = $stats['accepte']['montant'] ?? 0;

        // Calculer les remboursements
        $totalRembourse = $paiementRepo->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.montantRembourse), 0)')
            ->join('p.reservation', 'r')
            ->where('r.locataire = :locataire')
            ->andWhere('p.montantRembourse IS NOT NULL')
            ->setParameter('locataire', $locataire)
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('locataire/paiements.html.twig', [
            'paiements' => $paiements,
            'filtre' => $filtre,
            'stats' => $stats,
            'totalDepense' => $totalDepense,
            'totalRembourse' => (float) $totalRembourse,
        ]);
    }
}
