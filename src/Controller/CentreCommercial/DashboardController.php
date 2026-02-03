<?php

namespace App\Controller\CentreCommercial;

use App\Entity\CentreCommercial;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use App\Repository\EmplacementRepository;
use App\Repository\AvisRepository;
use App\Repository\ConversationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/centre', name: 'centre_')]
#[IsGranted('ROLE_CENTRE')]
class DashboardController extends AbstractController
{
    /**
     * Page principale du dashboard centre commercial
     */
    #[Route('', name: 'dashboard')]
    public function index(
        ReservationRepository $reservationRepo,
        EmplacementRepository $emplacementRepo,
        AvisRepository $avisRepo,
        ConversationRepository $conversationRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        // Calculer les stats
        $stats = $this->calculateStats($reservationRepo, $emplacementRepo, $avisRepo, $conversationRepo, $centre);

        // Récupérer les 10 dernières réservations EN_ATTENTE (à traiter en priorité)
        $reservationsEnAttente = $reservationRepo->createQueryBuilder('r')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->andWhere('r.statut = :statut')
            ->setParameter('centre', $centre)
            ->setParameter('statut', StatutReservation::EN_ATTENTE)
            ->orderBy('r.dateDemande', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Récupérer les 5 derniers messages non lus
        $messagesNonLus = [];
        $conversations = $conversationRepo->findBy(
            ['centreCommercial' => $centre, 'estArchivee' => false],
            ['dernierMessageDate' => 'DESC'],
            5
        );

        foreach ($conversations as $conversation) {
            $nbNonLus = $this->countUnreadMessages($conversation);
            if ($nbNonLus > 0) {
                $messagesNonLus[] = [
                    'conversation' => $conversation,
                    'nbNonLus' => $nbNonLus
                ];
            }
        }

        return $this->render('centre_commercial/dashboard.html.twig', [
            'centre' => $centre,
            'stats' => $stats,
            'reservations_en_attente' => $reservationsEnAttente,
            'messages' => $messagesNonLus,
            'statuts' => StatutReservation::cases(),
        ]);
    }

    /**
     * Page "Mon profil"
     */
    #[Route('/profil', name: 'profil')]
    public function profil(): Response
    {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        return $this->render('centre_commercial/profil.html.twig', [
            'centre' => $centre,
        ]);
    }

    /**
     * Modifier le profil du centre
     */
    #[Route('/profil/modifier', name: 'profil_edit', methods: ['POST'])]
    public function profilEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $centre->setNomCentre($request->request->get('nomCentre'));
        $centre->setEmail($request->request->get('email'));
        $centre->setTelephone($request->request->get('telephone'));
        $centre->setAdresse($request->request->get('adresse'));
        $centre->setCodePostal($request->request->get('codePostal'));
        $centre->setVille($request->request->get('ville'));
        $centre->setSiret($request->request->get('siret'));
        $centre->setNumeroTva($request->request->get('numeroTva'));
        $centre->setIban($request->request->get('iban'));
        $centre->setDescription($request->request->get('description'));

        // Gestion de l'upload de photo de profil
        $photoFile = $request->files->get('photo_profile');
        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

            // Vérifier le type de fichier
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($photoFile->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Format de fichier non autorisé. Utilisez JPG, PNG, GIF ou WebP.');
                return $this->redirectToRoute('centre_profil');
            }

            // Vérifier la taille (5MB max)
            if ($photoFile->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'Le fichier est trop volumineux (5MB maximum).');
                return $this->redirectToRoute('centre_profil');
            }

            try {
                $photoFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                    $newFilename
                );

                // Supprimer l'ancienne photo si elle existe
                $oldPhoto = $centre->getPhotoProfile();
                if ($oldPhoto) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                $centre->setPhotoProfile($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
            }
        }

        // Changement de mot de passe optionnel
        $nouveauMotDePasse = $request->request->get('nouveauMotDePasse');
        if (!empty($nouveauMotDePasse)) {
            if (strlen($nouveauMotDePasse) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('centre_profil');
            }

            $hashedPassword = $passwordHasher->hashPassword($centre, $nouveauMotDePasse);
            $centre->setPassword($hashedPassword);
        }

        try {
            $entityManager->flush();
            $this->addFlash('success', 'Profil modifié avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification du profil.');
        }

        return $this->redirectToRoute('centre_profil');
    }

    /**
     * Supprimer la photo de profil
     */
    #[Route('/profil/supprimer-photo', name: 'profil_delete_photo', methods: ['POST'])]
    public function deleteProfilePhoto(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_photo', $submittedToken)) {
            return new JsonResponse(['success' => false, 'message' => 'Token de sécurité invalide.'], 403);
        }

        $photo = $centre->getPhotoProfile();
        if ($photo) {
            $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $photo;
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
            $centre->setPhotoProfile(null);
            $entityManager->flush();
        }

        return new JsonResponse(['success' => true, 'message' => 'Photo de profil supprimée.']);
    }

    /**
     * Calculer les statistiques pour le dashboard
     */
    private function calculateStats(
        ReservationRepository $reservationRepo,
        EmplacementRepository $emplacementRepo,
        AvisRepository $avisRepo,
        ConversationRepository $conversationRepo,
        CentreCommercial $centre
    ): array {
        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime('first day of this month 00:00:00');
        $lastDayOfMonth = new \DateTime('last day of this month 23:59:59');

        $stats = [];

        // Compter les réservations EN_ATTENTE (à valider)
        $stats['reservations_en_attente'] = $reservationRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->andWhere('r.statut = :statut')
            ->setParameter('centre', $centre)
            ->setParameter('statut', StatutReservation::EN_ATTENTE)
            ->getQuery()
            ->getSingleScalarResult();

        // Calculer le CA du mois (somme montantLocation des réservations VALIDEE, EN_COURS, TERMINEE)
        $stats['ca_mois'] = $reservationRepo->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montantLocation), 0)')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->andWhere('r.statut IN (:statuts)')
            ->andWhere('r.dateDebut BETWEEN :debut AND :fin')
            ->setParameter('centre', $centre)
            ->setParameter('statuts', [StatutReservation::VALIDEE, StatutReservation::EN_COURS, StatutReservation::TERMINEE])
            ->setParameter('debut', $firstDayOfMonth)
            ->setParameter('fin', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();

        // Compter les emplacements actifs
        $stats['emplacements_actifs'] = $emplacementRepo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.centreCommercial = :centre')
            ->andWhere('e.statutAnnonce = :statut')
            ->setParameter('centre', $centre)
            ->setParameter('statut', 'active')
            ->getQuery()
            ->getSingleScalarResult();

        // Calculer la moyenne des avis
        $moyenneAvis = $avisRepo->createQueryBuilder('a')
            ->select('AVG(a.noteGlobale)')
            ->join('a.reservation', 'r')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->andWhere('a.estPublie = true')
            ->setParameter('centre', $centre)
            ->getQuery()
            ->getSingleScalarResult();

        $stats['moyenne_avis'] = $moyenneAvis ? round($moyenneAvis, 1) : 0;

        // Compter les messages non lus
        $conversations = $conversationRepo->findBy(
            ['centreCommercial' => $centre, 'estArchivee' => false]
        );

        $messagesNonLus = 0;
        foreach ($conversations as $conversation) {
            $messagesNonLus += $this->countUnreadMessages($conversation);
        }
        $stats['messages_non_lus'] = $messagesNonLus;

        // Compter par statut
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

        return $stats;
    }

    /**
     * Compter les messages non lus d'une conversation
     */
    private function countUnreadMessages($conversation): int
    {
        $count = 0;
        foreach ($conversation->getMessages() as $message) {
            if ($message->getTypeExpediteur() === 'locataire' && !$message->isEstLu()) {
                $count++;
            }
        }
        return $count;
    }
}
