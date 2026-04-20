<?php

namespace App\Controller\Locataire;

use App\Entity\Avis;
use App\Entity\Locataire;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/locataire/reservations', name: 'locataire_avis_')]
#[IsGranted('ROLE_LOCATAIRE')]
class AvisController extends AbstractController
{
    #[Route('/{id}/avis', name: 'nouveau', methods: ['GET', 'POST'])]
    public function nouveau(
        int $id,
        Request $request,
        ReservationRepository $reservationRepo,
        AvisRepository $avisRepo,
        EntityManagerInterface $em
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        // Seulement pour les réservations terminées
        if ($reservation->getStatut() !== StatutReservation::TERMINEE) {
            $this->addFlash('error', 'Vous ne pouvez laisser un avis que pour une réservation terminée.');
            return $this->redirectToRoute('locataire_reservation_detail', ['id' => $id]);
        }

        // Vérifier si un avis existe déjà
        $avisExistant = $avisRepo->findOneBy(['reservation' => $reservation]);
        if ($avisExistant) {
            $this->addFlash('info', 'Vous avez déjà laissé un avis pour cette réservation.');
            return $this->redirectToRoute('locataire_reservation_detail', ['id' => $id]);
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('avis_' . $id, $token)) {
                $this->addFlash('error', 'Token de sécurité invalide.');
                return $this->redirectToRoute('locataire_avis_nouveau', ['id' => $id]);
            }

            $noteGlobale = (int) $request->request->get('note_globale');
            if ($noteGlobale < 1 || $noteGlobale > 5) {
                $this->addFlash('error', 'La note globale doit être entre 1 et 5.');
                return $this->redirectToRoute('locataire_avis_nouveau', ['id' => $id]);
            }

            $avis = new Avis();
            $avis->setReservation($reservation);
            $avis->setNoteGlobale($noteGlobale);
            $avis->setNotePropreteConformite((int) $request->request->get('note_proprete') ?: null);
            $avis->setNoteEmplacement((int) $request->request->get('note_emplacement') ?: null);
            $avis->setNoteQualitePrix((int) $request->request->get('note_qualite_prix') ?: null);
            $avis->setNoteCommunication((int) $request->request->get('note_communication') ?: null);
            $avis->setCommentaire(htmlspecialchars($request->request->get('commentaire') ?? ''));
            $avis->setTypeAuteur('locataire');
            $avis->setDateCreation(new \DateTime());
            $avis->setEstPublie(false);

            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Votre avis a été soumis et sera publié après modération.');
            return $this->redirectToRoute('locataire_reservation_detail', ['id' => $id]);
        }

        return $this->render('locataire/avis/nouveau.html.twig', [
            'reservation' => $reservation,
        ]);
    }
}
