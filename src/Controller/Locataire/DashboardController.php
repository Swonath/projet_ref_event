<?php

namespace App\Controller\Locataire;

use App\Entity\Locataire;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use App\Repository\EmplacementRepository;
use App\Repository\FavoriRepository;
use App\Repository\DocumentRepository;
use App\Repository\ConversationRepository;
use App\Service\EmailNotificationService;
use App\Service\PdfService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/dashboard/locataire', name: 'locataire_')]
#[IsGranted('ROLE_LOCATAIRE')]
class DashboardController extends AbstractController
{
    /**
     * Page principale du dashboard locataire
     */
    #[Route('', name: 'dashboard')]
    public function index(
        ReservationRepository $reservationRepo,
        FavoriRepository $favoriRepo,
        ConversationRepository $conversationRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        // Afficher TOUTES les réservations (maximum 10)
        $reservations = $reservationRepo->findBy(
            ['locataire' => $locataire],
            ['dateDebut' => 'DESC'],
            10
        );

        // Calculer tous les compteurs (avec favoris)
        $stats = $this->calculateStats($reservationRepo, $favoriRepo, $locataire);

        // Récupérer les 5 dernières conversations avec messages
        $messages = $conversationRepo->createQueryBuilder('c')
            ->where('c.locataire = :locataire')
            ->andWhere('c.estArchivee = false')
            ->setParameter('locataire', $locataire)
            ->orderBy('c.dernierMessageDate', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return $this->render('locataire/dashboard.html.twig', [
            'locataire' => $locataire,
            'stats' => $stats,
            'reservations' => $reservations,
            'messages' => $messages,
            'statuts' => StatutReservation::cases(),
        ]);
    }
    
    /**
     * Page "Mes réservations" avec filtres par statut ET période
     * MODIFIÉ : Ajoute la pagination (limite à 12 + totalReservations)
     */
    #[Route('/reservations', name: 'reservations')]
    public function reservations(
        Request $request,
        ReservationRepository $reservationRepo,
        FavoriRepository $favoriRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $filtre = $request->query->get('filtre', 'toutes');
        
        $now = new \DateTime();
        
        $qb = $reservationRepo->createQueryBuilder('r')
            ->where('r.locataire = :locataire')
            ->setParameter('locataire', $locataire);
        
        switch ($filtre) {
            case 'en_attente':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::EN_ATTENTE)
                   ->orderBy('r.dateDemande', 'DESC');
                break;
                
            case 'validee':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::VALIDEE)
                   ->orderBy('r.dateDebut', 'ASC');
                break;
                
            case 'en_cours':
                $qb->andWhere('r.statut = :statut')
                   ->andWhere('r.dateDebut <= :now')
                   ->andWhere('r.dateFin >= :now')
                   ->setParameter('statut', StatutReservation::EN_COURS)
                   ->setParameter('now', $now)
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
            
            case 'actives':
                $qb->andWhere('r.dateDebut <= :now')
                   ->andWhere('r.dateFin >= :now')
                   ->andWhere('r.statut IN (:statuts_actifs)')
                   ->setParameter('now', $now)
                   ->setParameter('statuts_actifs', [
                       StatutReservation::VALIDEE,
                       StatutReservation::EN_COURS,
                   ])
                   ->orderBy('r.dateFin', 'ASC');
                break;
                
            case 'a_venir':
                $qb->andWhere('r.dateDebut > :now')
                   ->andWhere('r.statut NOT IN (:statuts_exclus)')
                   ->setParameter('now', $now)
                   ->setParameter('statuts_exclus', [
                       StatutReservation::REFUSEE,
                       StatutReservation::ANNULEE,
                   ])
                   ->orderBy('r.dateDebut', 'ASC');
                break;
                
            case 'passees':
                $qb->andWhere('r.dateFin < :now')
                   ->andWhere('r.statut = :statut')
                   ->setParameter('now', $now)
                   ->setParameter('statut', StatutReservation::TERMINEE)
                   ->orderBy('r.dateFin', 'DESC');
                break;
                
            default:
                $qb->orderBy('r.dateDebut', 'DESC');
                break;
        }
        
        // NOUVEAU : Compter le TOTAL avant de limiter
        $totalReservations = count($qb->getQuery()->getResult());
        
        // NOUVEAU : Limiter à 12 résultats
        $reservations = $qb->setMaxResults(12)
                          ->getQuery()
                          ->getResult();
        
        $stats = $this->calculateStats($reservationRepo, $favoriRepo, $locataire);
        
        return $this->render('locataire/reservations.html.twig', [
            'locataire' => $locataire,
            'reservations' => $reservations,
            'totalReservations' => $totalReservations,  // NOUVEAU
            'filtre_actif' => $filtre,
            'stats' => $stats,
            'statuts' => StatutReservation::cases(),
        ]);
    }
    
    /**
     * NOUVEAU : Charger plus de réservations (AJAX)
     * VERSION SANS TEMPLATE PARTIEL - Génère le HTML directement en PHP
     */
    #[Route('/reservations/charger-plus', name: 'reservations_charger_plus', methods: ['GET'])]
    public function chargerPlusReservations(
        Request $request,
        ReservationRepository $reservationRepo,
        UrlGeneratorInterface $urlGenerator
    ): JsonResponse {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $offset = $request->query->getInt('offset', 12);
        $filtre = $request->query->get('filtre', 'toutes');
        
        $now = new \DateTime();
        
        $qb = $reservationRepo->createQueryBuilder('r')
            ->where('r.locataire = :locataire')
            ->setParameter('locataire', $locataire);
        
        // Appliquer le même filtre (copie du switch de reservations())
        switch ($filtre) {
            case 'en_attente':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::EN_ATTENTE)
                   ->orderBy('r.dateDemande', 'DESC');
                break;
            case 'validee':
                $qb->andWhere('r.statut = :statut')
                   ->setParameter('statut', StatutReservation::VALIDEE)
                   ->orderBy('r.dateDebut', 'ASC');
                break;
            case 'en_cours':
                $qb->andWhere('r.statut = :statut')
                   ->andWhere('r.dateDebut <= :now')
                   ->andWhere('r.dateFin >= :now')
                   ->setParameter('statut', StatutReservation::EN_COURS)
                   ->setParameter('now', $now)
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
            case 'actives':
                $qb->andWhere('r.dateDebut <= :now')
                   ->andWhere('r.dateFin >= :now')
                   ->andWhere('r.statut IN (:statuts_actifs)')
                   ->setParameter('now', $now)
                   ->setParameter('statuts_actifs', [
                       StatutReservation::VALIDEE,
                       StatutReservation::EN_COURS,
                   ])
                   ->orderBy('r.dateFin', 'ASC');
                break;
            case 'a_venir':
                $qb->andWhere('r.dateDebut > :now')
                   ->andWhere('r.statut NOT IN (:statuts_exclus)')
                   ->setParameter('now', $now)
                   ->setParameter('statuts_exclus', [
                       StatutReservation::REFUSEE,
                       StatutReservation::ANNULEE,
                   ])
                   ->orderBy('r.dateDebut', 'ASC');
                break;
            case 'passees':
                $qb->andWhere('r.dateFin < :now')
                   ->andWhere('r.statut = :statut')
                   ->setParameter('now', $now)
                   ->setParameter('statut', StatutReservation::TERMINEE)
                   ->orderBy('r.dateFin', 'DESC');
                break;
            default:
                $qb->orderBy('r.dateDebut', 'DESC');
                break;
        }
        
        $totalReservations = count($qb->getQuery()->getResult());
        
        $reservations = $qb->setFirstResult($offset)
                          ->setMaxResults(12)
                          ->getQuery()
                          ->getResult();
        
        $hasMore = ($offset + 12) < $totalReservations;
        
        // Générer le HTML directement en PHP (pas de template Twig)
        $html = '';
        
        foreach ($reservations as $reservation) {
            $emplacement = $reservation->getEmplacement();
            $photos = $emplacement->getPhotos();
            
            // URL de l'image ou placeholder
            $imageHtml = '';
            if (count($photos) > 0) {
                $photoPath = '/uploads/photos/' . $photos[0]->getCheminFichier();
                $imageHtml = '<img src="' . $photoPath . '" alt="' . htmlspecialchars($emplacement->getTitreAnnonce()) . '">';
            } else {
                $imageHtml = '<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f5f5f5; color:#999;">📷</div>';
            }
            
            // URL de détail
            $detailUrl = $urlGenerator->generate('locataire_reservation_detail', ['id' => $reservation->getId()]);
            
            // Formatage des dates
            $dateDebut = $reservation->getDateDebut()->format('d/m/Y');
            $dateFin = $reservation->getDateFin()->format('d/m/Y');
            
            // Générer le HTML de la card
            $html .= '
            <div class="reservation-card">
                <div class="reservation-image">' . $imageHtml . '</div>
                
                <div class="reservation-info">
                    <h3>' . htmlspecialchars($emplacement->getTitreAnnonce()) . '</h3>
                    <div class="reservation-details">
                        ' . htmlspecialchars($emplacement->getCentreCommercial()->getNomCentre()) . ' - 
                        ' . htmlspecialchars($emplacement->getTypeEmplacement()) . ' -
                        ' . $emplacement->getSurface() . ' m²
                    </div>
                    <div class="reservation-details">
                        Du ' . $dateDebut . ' au ' . $dateFin . '
                    </div>
                    <span class="reservation-status status-' . $reservation->getStatutCssClass() . '">
                        ' . $reservation->getStatut()->icone . ' ' . $reservation->getStatutLibelle() . '
                    </span>
                </div>
                
                <div class="reservation-actions">
                    <div class="reservation-price">' . $reservation->getMontantTotal() . ' €</div>
                    <a href="' . $detailUrl . '" class="btn-small btn-primary-small">Voir détails</a>
                    <a href="#" class="btn-small btn-secondary-small">Contacter</a>
                </div>
            </div>';
        }
        
        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'hasMore' => $hasMore,
            'count' => count($reservations),
            'offset' => $offset
        ]);
    }
    
    /**
     * Calcule les statistiques pour le dashboard et les filtres
     */
    private function calculateStats(
        ReservationRepository $reservationRepo,
        FavoriRepository $favoriRepo,
        Locataire $locataire
    ): array {
        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime('first day of this month 00:00:00');
        $lastDayOfMonth = new \DateTime('last day of this month 23:59:59');
        
        $totalReservations = $reservationRepo->count(['locataire' => $locataire]);
        
        $stats = [
            'total_reservations' => $totalReservations,
        ];
        
        // Compter pour chaque statut
        foreach (StatutReservation::cases() as $statut) {
            $count = $reservationRepo->count([
                'locataire' => $locataire,
                'statut' => $statut
            ]);
            $stats['statut_' . $statut->value] = $count;
        }
        
        // Réservations actives (en cours avec dates valides)
        $stats['reservations_actives'] = $reservationRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.locataire = :locataire')
            ->andWhere('r.dateDebut <= :now')
            ->andWhere('r.dateFin >= :now')
            ->andWhere('r.statut IN (:statuts_actifs)')
            ->setParameter('locataire', $locataire)
            ->setParameter('now', $now)
            ->setParameter('statuts_actifs', [
                StatutReservation::VALIDEE,
                StatutReservation::EN_COURS,
            ])
            ->getQuery()
            ->getSingleScalarResult();
        
        // Réservations à venir (futures + pas annulées/refusées)
        $stats['reservations_a_venir'] = $reservationRepo->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.locataire = :locataire')
            ->andWhere('r.dateDebut > :now')
            ->andWhere('r.statut NOT IN (:statuts_exclus)')
            ->setParameter('locataire', $locataire)
            ->setParameter('now', $now)
            ->setParameter('statuts_exclus', [
                StatutReservation::REFUSEE,
                StatutReservation::ANNULEE,
            ])
            ->getQuery()
            ->getSingleScalarResult();
        
        // Réservations passées (terminées)
        $stats['reservations_passees'] = $reservationRepo->count([
            'locataire' => $locataire,
            'statut' => StatutReservation::TERMINEE
        ]);
        
        // Dépenses du mois en cours
        $depensesMois = $reservationRepo->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.montantTotal), 0)')
            ->where('r.locataire = :locataire')
            ->andWhere('r.dateDebut >= :firstDay')
            ->andWhere('r.dateDebut <= :lastDay')
            ->setParameter('locataire', $locataire)
            ->setParameter('firstDay', $firstDayOfMonth)
            ->setParameter('lastDay', $lastDayOfMonth)
            ->getQuery()
            ->getSingleScalarResult();
        
        $stats['messages_non_lus'] = 0;
        
        // Compter les favoris
        $stats['favoris'] = $favoriRepo->countByLocataire($locataire);
        
        $stats['depenses_mois'] = round($depensesMois, 2);
        
        return $stats;
    }
    
    /**
     * Détail d'une réservation
     */
    #[Route('/reservations/{id}', name: 'reservation_detail', requirements: ['id' => '\d+'])]
    public function reservationDetail(
        int $id,
        ReservationRepository $reservationRepo,
        ConversationRepository $conversationRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        // Chercher une conversation liée à cette réservation ou avec ce centre
        $conversation = $conversationRepo->findOneBy([
            'locataire' => $locataire,
            'reservation' => $reservation
        ]);

        // Si pas de conversation liée à la réservation, chercher une avec le centre
        if (!$conversation) {
            $conversation = $conversationRepo->findOneBy([
                'locataire' => $locataire,
                'centreCommercial' => $reservation->getEmplacement()->getCentreCommercial()
            ]);
        }

        return $this->render('locataire/reservation_detail.html.twig', [
            'reservation' => $reservation,
            'conversation' => $conversation,
        ]);
    }

    /**
     * Annuler une réservation
     */
    #[Route('/reservations/{id}/annuler', name: 'reservation_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function annulerReservation(
        int $id,
        Request $request,
        ReservationRepository $reservationRepo,
        EntityManagerInterface $entityManager,
        EmailNotificationService $emailNotification
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('annuler_reservation_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('locataire_reservation_detail', ['id' => $id]);
        }

        // Vérifier que la réservation peut être annulée
        $statutsAnnulables = [StatutReservation::EN_ATTENTE, StatutReservation::VALIDEE];
        if (!in_array($reservation->getStatut(), $statutsAnnulables)) {
            $this->addFlash('error', 'Cette réservation ne peut pas être annulée.');
            return $this->redirectToRoute('locataire_reservation_detail', ['id' => $id]);
        }

        // Annuler la réservation
        $reservation->setStatut(StatutReservation::ANNULEE);
        $reservation->setAnnuleePar('locataire');
        $reservation->setDateAnnulation(new \DateTime());

        $entityManager->flush();

        $emailNotification->notifierReservationAnnulee($reservation, 'locataire');

        $this->addFlash('success', 'Votre réservation a été annulée avec succès.');

        return $this->redirectToRoute('locataire_reservations');
    }
    
    /**
     * Télécharger la facture PDF d'une réservation
     */
    #[Route('/reservations/{id}/facture', name: 'reservation_facture', requirements: ['id' => '\d+'])]
    public function telechargerFacture(
        int $id,
        ReservationRepository $reservationRepo,
        PdfService $pdfService
    ): StreamedResponse {
        /** @var \App\Entity\Locataire $locataire */
        $locataire = $this->getUser();

        $reservation = $reservationRepo->find($id);

        if (!$reservation || $reservation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }

        $pdfContent = $pdfService->genererFacture($reservation);
        $filename = 'facture-reservation-' . $reservation->getId() . '.pdf';

        return new StreamedResponse(function () use ($pdfContent) {
            echo $pdfContent;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Page "Mon profil"
     */
    #[Route('/profil', name: 'profil')]
    public function profil(): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        return $this->render('locataire/profil.html.twig', [
            'locataire' => $locataire,
        ]);
    }
    
    /**
     * Modifier le profil
     */
    #[Route('/profil/modifier', name: 'profil_edit', methods: ['POST'])]
    public function profilEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        $locataire->setNom($request->request->get('nom'));
        $locataire->setEmail($request->request->get('email'));
        $locataire->setTelephone($request->request->get('telephone'));
        $locataire->setAdresseFacturation($request->request->get('adresse'));
        $locataire->setCodePostal($request->request->get('codePostal'));
        $locataire->setVille($request->request->get('ville'));

        if (method_exists($locataire, 'setSiret')) {
            $locataire->setSiret($request->request->get('siret'));
        }
        if (method_exists($locataire, 'setTypeActivite')) {
            $locataire->setTypeActivite($request->request->get('typeActivite'));
        }

        // Gestion de l'upload de photo de profil
        $photoFile = $request->files->get('photo_profile');
        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

            // Vérifier le type de fichier
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($photoFile->getMimeType(), $allowedMimeTypes)) {
                $this->addFlash('error', 'Le fichier doit être une image (JPG, PNG, GIF ou WebP).');
                return $this->redirectToRoute('locataire_profil');
            }

            // Vérifier la taille (max 5MB)
            if ($photoFile->getSize() > 5 * 1024 * 1024) {
                $this->addFlash('error', 'L\'image ne doit pas dépasser 5 Mo.');
                return $this->redirectToRoute('locataire_profil');
            }

            try {
                // Supprimer l'ancienne photo si elle existe
                $oldPhoto = $locataire->getPhotoProfile();
                if ($oldPhoto) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Déplacer le nouveau fichier
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $photoFile->move($uploadDir, $newFilename);
                $locataire->setPhotoProfile($newFilename);
            } catch (FileException $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de la photo.');
                return $this->redirectToRoute('locataire_profil');
            }
        }

        $nouveauMotDePasse = $request->request->get('nouveau_mot_de_passe');
        $confirmerMotDePasse = $request->request->get('confirmer_mot_de_passe');

        if (!empty($nouveauMotDePasse)) {
            if ($nouveauMotDePasse !== $confirmerMotDePasse) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('locataire_profil');
            }

            if (strlen($nouveauMotDePasse) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('locataire_profil');
            }

            $hashedPassword = $passwordHasher->hashPassword($locataire, $nouveauMotDePasse);
            $locataire->setPassword($hashedPassword);
        }

        try {
            $entityManager->flush();
            $this->addFlash('success', 'Profil modifié avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de la modification du profil.');
        }

        return $this->redirectToRoute('locataire_profil');
    }

    /**
     * Supprimer la photo de profil
     */
    #[Route('/profil/supprimer-photo', name: 'profil_delete_photo', methods: ['POST'])]
    public function deleteProfilePhoto(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_photo', $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('locataire_profil');
        }

        $photo = $locataire->getPhotoProfile();
        if ($photo) {
            $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/' . $photo;
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
            $locataire->setPhotoProfile(null);
            $entityManager->flush();
            $this->addFlash('success', 'Photo de profil supprimée.');
        }

        return $this->redirectToRoute('locataire_profil');
    }
    
    /**
     * Page "Mes documents"
     */
    #[Route('/documents', name: 'documents')]
    public function documents(
        Request $request,
        DocumentRepository $documentRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();

        $filtre = $request->query->get('type', 'tous');

        // Récupérer tous les documents des réservations du locataire
        $qb = $documentRepo->createQueryBuilder('d')
            ->join('d.reservation', 'r')
            ->where('r.locataire = :locataire')
            ->setParameter('locataire', $locataire)
            ->orderBy('d.dateGeneration', 'DESC');

        // Appliquer le filtre par type
        if ($filtre !== 'tous') {
            $qb->andWhere('d.typeDocument = :type')
               ->setParameter('type', $filtre);
        }

        $documents = $qb->getQuery()->getResult();

        // Calculer les statistiques
        $stats = [
            'contrats' => $documentRepo->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->join('d.reservation', 'r')
                ->where('r.locataire = :locataire')
                ->andWhere('d.typeDocument = :type')
                ->setParameter('locataire', $locataire)
                ->setParameter('type', 'contrat')
                ->getQuery()
                ->getSingleScalarResult(),
            'factures' => $documentRepo->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->join('d.reservation', 'r')
                ->where('r.locataire = :locataire')
                ->andWhere('d.typeDocument = :type')
                ->setParameter('locataire', $locataire)
                ->setParameter('type', 'facture')
                ->getQuery()
                ->getSingleScalarResult(),
            'devis' => $documentRepo->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->join('d.reservation', 'r')
                ->where('r.locataire = :locataire')
                ->andWhere('d.typeDocument = :type')
                ->setParameter('locataire', $locataire)
                ->setParameter('type', 'devis')
                ->getQuery()
                ->getSingleScalarResult(),
        ];

        return $this->render('locataire/documents.html.twig', [
            'documents' => $documents,
            'filtre' => $filtre,
            'stats' => $stats,
        ]);
    }
    
}