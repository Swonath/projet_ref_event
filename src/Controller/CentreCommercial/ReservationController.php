<?php

namespace App\Controller\CentreCommercial;

use App\Entity\CentreCommercial;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/dashboard/centre/reservations', name: 'centre_reservations_')]
#[IsGranted('ROLE_CENTRE')]
class ReservationController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        ReservationRepository $reservationRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $filtre = $request->query->get('filtre', 'toutes');
        $now = new \DateTime();

        // CRITIQUE: Join via emplacement
        $qb = $reservationRepo->createQueryBuilder('r')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->setParameter('centre', $centre);

        // Appliquer les filtres
        switch ($filtre) {
            case 'en_attente':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::EN_ATTENTE)
                   ->orderBy('r.dateDemande', 'ASC');
                break;

            case 'validee':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::VALIDEE)
                   ->orderBy('r.dateDebut', 'ASC');
                break;

            case 'en_cours':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::EN_COURS)
                   ->orderBy('r.dateFin', 'ASC');
                break;

            case 'terminee':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::TERMINEE)
                   ->orderBy('r.dateFin', 'DESC');
                break;

            case 'refusee':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::REFUSEE)
                   ->orderBy('r.dateDemande', 'DESC');
                break;

            case 'annulee':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::ANNULEE)
                   ->orderBy('r.dateAnnulation', 'DESC');
                break;

            default:
                $qb->orderBy('r.dateDebut', 'DESC');
                break;
        }

        $totalReservations = count($qb->getQuery()->getResult());
        $reservations = $qb->setMaxResults(12)->getQuery()->getResult();

        $stats = $this->calculateStats($reservationRepo, $centre);

        return $this->render('centre_commercial/reservations.html.twig', [
            'centre' => $centre,
            'reservations' => $reservations,
            'totalReservations' => $totalReservations,
            'filtre_actif' => $filtre,
            'stats' => $stats,
            'statuts' => StatutReservation::cases(),
        ]);
    }

    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(
        int $id,
        ReservationRepository $reservationRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getEmplacement()->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        return $this->render('centre_commercial/reservation_detail.html.twig', [
            'centre' => $centre,
            'reservation' => $reservation,
        ]);
    }

    #[Route('/{id}/valider', name: 'valider', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function valider(
        int $id,
        Request $request,
        ReservationRepository $reservationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getEmplacement()->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('valider_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_reservations_index');
        }

        if ($reservation->getStatut() !== StatutReservation::EN_ATTENTE) {
            $this->addFlash('error', 'Cette réservation ne peut pas être validée.');
            return $this->redirectToRoute('centre_reservations_detail', ['id' => $id]);
        }

        $reservation->setStatut(StatutReservation::VALIDEE);
        $reservation->setDateValidation(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Réservation validée avec succès !');
        return $this->redirectToRoute('centre_reservations_detail', ['id' => $id]);
    }

    #[Route('/{id}/refuser', name: 'refuser', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function refuser(
        int $id,
        Request $request,
        ReservationRepository $reservationRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getEmplacement()->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('refuser_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_reservations_index');
        }

        if ($reservation->getStatut() !== StatutReservation::EN_ATTENTE) {
            $this->addFlash('error', 'Cette réservation ne peut pas être refusée.');
            return $this->redirectToRoute('centre_reservations_detail', ['id' => $id]);
        }

        $motifRefus = $request->request->get('motifRefus');
        if (empty($motifRefus)) {
            $this->addFlash('error', 'Le motif de refus est obligatoire.');
            return $this->redirectToRoute('centre_reservations_detail', ['id' => $id]);
        }

        $reservation->setStatut(StatutReservation::REFUSEE);
        $reservation->setMotifRefus($motifRefus);
        $reservation->setDateValidation(new \DateTime());
        $entityManager->flush();

        $this->addFlash('success', 'Réservation refusée.');
        return $this->redirectToRoute('centre_reservations_index');
    }

    private function calculateStats(ReservationRepository $reservationRepo, CentreCommercial $centre): array
    {
        $stats = [];

        foreach (StatutReservation::cases() as $statut) {
            $count = $reservationRepo->createQueryBuilder('r')
                ->select('COUNT(r.id)')
                ->join('r.emplacement', 'e')
                ->where('e.centreCommercial = :centre')
                ->andWhere('r.statut = :statut')
                ->setParameter('centre', $centre)
                ->setParameter('statut', $statut)
                ->getQuery()
                ->getSingleScalarResult();

            $stats['statut_' . $statut->value] = $count;
        }

        $stats['total_reservations'] = array_sum($stats);

        return $stats;
    }
}
