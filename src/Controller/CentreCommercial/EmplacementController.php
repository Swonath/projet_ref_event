<?php

namespace App\Controller\CentreCommercial;

use App\Entity\CentreCommercial;
use App\Entity\Emplacement;
use App\Entity\Photo;
use App\Entity\PeriodeIndisponibilite;
use App\Form\EmplacementType;
use App\Form\PeriodeIndisponibiliteType;
use App\Repository\EmplacementRepository;
use App\Repository\PhotoRepository;
use App\Repository\PeriodeIndisponibiliteRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/dashboard/centre/emplacements', name: 'centre_emplacements_')]
#[IsGranted('ROLE_CENTRE')]
class EmplacementController extends AbstractController
{
    /**
     * Liste des emplacements du centre
     */
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $filtre = $request->query->get('filtre', 'tous');

        $qb = $emplacementRepo->createQueryBuilder('e')
            ->where('e.centreCommercial = :centre')
            ->setParameter('centre', $centre)
            ->orderBy('e.dateCreation', 'DESC');

        // Appliquer le filtre
        switch ($filtre) {
            case 'actifs':
                $qb->andWhere('e.statutAnnonce = :statut')
                   ->setParameter('statut', 'active');
                break;
            case 'inactifs':
                $qb->andWhere('e.statutAnnonce = :statut')
                   ->setParameter('statut', 'inactive');
                break;
        }

        $totalEmplacements = count($qb->getQuery()->getResult());
        $emplacements = $qb->setMaxResults(12)->getQuery()->getResult();

        // Calculer les stats
        $stats = [
            'total' => $emplacementRepo->count(['centreCommercial' => $centre]),
            'actifs' => $emplacementRepo->count(['centreCommercial' => $centre, 'statutAnnonce' => 'active']),
            'inactifs' => $emplacementRepo->count(['centreCommercial' => $centre, 'statutAnnonce' => 'inactive']),
        ];

        return $this->render('centre_commercial/emplacements/liste.html.twig', [
            'centre' => $centre,
            'emplacements' => $emplacements,
            'totalEmplacements' => $totalEmplacements,
            'filtre_actif' => $filtre,
            'stats' => $stats,
        ]);
    }

    /**
     * Charger plus d'emplacements (AJAX)
     */
    #[Route('/charger-plus', name: 'charger_plus', methods: ['GET'])]
    public function chargerPlusEmplacements(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): JsonResponse {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $offset = $request->query->getInt('offset', 12);
        $filtre = $request->query->get('filtre', 'tous');

        $qb = $emplacementRepo->createQueryBuilder('e')
            ->where('e.centreCommercial = :centre')
            ->setParameter('centre', $centre)
            ->orderBy('e.dateCreation', 'DESC');

        switch ($filtre) {
            case 'actifs':
                $qb->andWhere('e.statutAnnonce = :statut')
                   ->setParameter('statut', 'active');
                break;
            case 'inactifs':
                $qb->andWhere('e.statutAnnonce = :statut')
                   ->setParameter('statut', 'inactive');
                break;
        }

        $emplacements = $qb->setFirstResult($offset)
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

        $html = '';
        foreach ($emplacements as $emplacement) {
            $photoUrl = null;
            if ($emplacement->getPhotos()->count() > 0) {
                $photoUrl = '/uploads/photos/' . $emplacement->getPhotos()->first()->getCheminFichier();
            }

            $html .= $this->renderView('centre_commercial/emplacements/_card.html.twig', [
                'emplacement' => $emplacement,
                'photoUrl' => $photoUrl
            ]);
        }

        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'count' => count($emplacements),
        ]);
    }

    /**
     * Créer un nouvel emplacement
     */
    #[Route('/creer', name: 'creer')]
    public function creer(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $emplacement = new Emplacement();
        $emplacement->setCentreCommercial($centre);
        $emplacement->setDateCreation(new \DateTime());
        $emplacement->setStatutAnnonce('active');

        $form = $this->createForm(EmplacementType::class, $emplacement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emplacement->setDateModification(new \DateTime());

            // Gérer les uploads de photos
            $photoFiles = $request->files->get('photos', []);
            $uploadedCount = 0;

            foreach ($photoFiles as $photoFile) {
                if ($photoFile && $uploadedCount < 10) {
                    $result = $this->uploadPhoto($photoFile, $emplacement, $slugger, $entityManager);
                    if ($result['success']) {
                        $uploadedCount++;
                    }
                }
            }

            $entityManager->persist($emplacement);
            $entityManager->flush();

            $this->addFlash('success', 'Emplacement créé avec succès !');

            return $this->redirectToRoute('centre_emplacements_detail', ['id' => $emplacement->getId()]);
        }

        return $this->render('centre_commercial/emplacements/creer.html.twig', [
            'centre' => $centre,
            'form' => $form,
        ]);
    }

    /**
     * Modifier un emplacement
     */
    #[Route('/{id}/modifier', name: 'modifier', requirements: ['id' => '\d+'])]
    public function modifier(
        int $id,
        Request $request,
        EmplacementRepository $emplacementRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement || $emplacement->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Emplacement non trouvé');
        }

        $form = $this->createForm(EmplacementType::class, $emplacement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emplacement->setDateModification(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Emplacement modifié avec succès !');

            return $this->redirectToRoute('centre_emplacements_detail', ['id' => $emplacement->getId()]);
        }

        return $this->render('centre_commercial/emplacements/modifier.html.twig', [
            'centre' => $centre,
            'emplacement' => $emplacement,
            'form' => $form,
        ]);
    }

    /**
     * Détail d'un emplacement
     */
    #[Route('/{id}', name: 'detail', requirements: ['id' => '\d+'])]
    public function detail(
        int $id,
        EmplacementRepository $emplacementRepo,
        ReservationRepository $reservationRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement || $emplacement->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Emplacement non trouvé');
        }

        // Récupérer les réservations liées
        $reservations = $reservationRepo->findBy(
            ['emplacement' => $emplacement],
            ['dateDebut' => 'DESC'],
            10
        );

        // Formulaire pour ajouter une période d'indisponibilité
        $periode = new PeriodeIndisponibilite();
        $periode->setEmplacement($emplacement);
        $periodeForm = $this->createForm(PeriodeIndisponibiliteType::class, $periode);

        return $this->render('centre_commercial/emplacements/detail.html.twig', [
            'centre' => $centre,
            'emplacement' => $emplacement,
            'reservations' => $reservations,
            'periodeForm' => $periodeForm,
        ]);
    }

    /**
     * Supprimer un emplacement
     */
    #[Route('/{id}/supprimer', name: 'supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function supprimer(
        int $id,
        Request $request,
        EmplacementRepository $emplacementRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement || $emplacement->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Emplacement non trouvé');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_emplacements_detail', ['id' => $id]);
        }

        // Vérifier qu'il n'y a pas de réservations actives ou à venir
        $hasActiveReservations = false;
        foreach ($emplacement->getReservations() as $reservation) {
            $statut = $reservation->getStatut()->value;
            if (in_array($statut, ['en_attente', 'validee', 'en_cours'])) {
                $hasActiveReservations = true;
                break;
            }
        }

        if ($hasActiveReservations) {
            $this->addFlash('error', 'Impossible de supprimer cet emplacement : il a des réservations actives ou à venir.');
            return $this->redirectToRoute('centre_emplacements_detail', ['id' => $id]);
        }

        // Supprimer les fichiers photos
        foreach ($emplacement->getPhotos() as $photo) {
            $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/photos/' . $photo->getCheminFichier();
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $entityManager->remove($emplacement);
        $entityManager->flush();

        $this->addFlash('success', 'Emplacement supprimé avec succès.');

        return $this->redirectToRoute('centre_emplacements_index');
    }

    /**
     * Ajouter une photo (AJAX)
     */
    #[Route('/{id}/photo/ajouter', name: 'ajouter_photo', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ajouterPhoto(
        int $id,
        Request $request,
        EmplacementRepository $emplacementRepo,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): JsonResponse {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement || $emplacement->getCentreCommercial() !== $centre) {
            return new JsonResponse(['success' => false, 'message' => 'Emplacement non trouvé'], 404);
        }

        // Vérifier le nombre de photos
        if ($emplacement->getPhotos()->count() >= 10) {
            return new JsonResponse(['success' => false, 'message' => 'Nombre maximum de photos atteint (10)'], 400);
        }

        $photoFile = $request->files->get('photo');
        if (!$photoFile) {
            return new JsonResponse(['success' => false, 'message' => 'Aucun fichier fourni'], 400);
        }

        $result = $this->uploadPhoto($photoFile, $emplacement, $slugger, $entityManager);

        if ($result['success']) {
            $entityManager->flush();
            return new JsonResponse([
                'success' => true,
                'message' => 'Photo ajoutée avec succès',
                'photo' => [
                    'id' => $result['photo']->getId(),
                    'url' => '/uploads/photos/' . $result['photo']->getCheminFichier()
                ]
            ]);
        }

        return new JsonResponse(['success' => false, 'message' => $result['message']], 400);
    }

    /**
     * Supprimer une photo (AJAX)
     */
    #[Route('/photo/{photoId}/supprimer', name: 'supprimer_photo', methods: ['POST'], requirements: ['photoId' => '\d+'])]
    public function supprimerPhoto(
        int $photoId,
        Request $request,
        PhotoRepository $photoRepo,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $photo = $photoRepo->find($photoId);

        if (!$photo || $photo->getEmplacement()->getCentreCommercial() !== $centre) {
            return new JsonResponse(['success' => false, 'message' => 'Photo non trouvée'], 404);
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_photo_' . $photoId, $submittedToken)) {
            return new JsonResponse(['success' => false, 'message' => 'Token de sécurité invalide'], 403);
        }

        // Supprimer le fichier physique
        $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/photos/' . $photo->getCheminFichier();
        if (file_exists($photoPath)) {
            unlink($photoPath);
        }

        $entityManager->remove($photo);
        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Photo supprimée avec succès']);
    }

    /**
     * Ajouter une période d'indisponibilité
     */
    #[Route('/{id}/periode-indispo/ajouter', name: 'ajouter_periode_indispo', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function ajouterPeriodeIndispo(
        int $id,
        Request $request,
        EmplacementRepository $emplacementRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement || $emplacement->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Emplacement non trouvé');
        }

        $periode = new PeriodeIndisponibilite();
        $periode->setEmplacement($emplacement);

        $form = $this->createForm(PeriodeIndisponibiliteType::class, $periode);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que dateDebut < dateFin
            if ($periode->getDateDebut() >= $periode->getDateFin()) {
                $this->addFlash('error', 'La date de fin doit être postérieure à la date de début.');
                return $this->redirectToRoute('centre_emplacements_detail', ['id' => $id]);
            }

            $entityManager->persist($periode);
            $entityManager->flush();

            $this->addFlash('success', 'Période d\'indisponibilité ajoutée avec succès.');
        } else {
            $this->addFlash('error', 'Erreur lors de l\'ajout de la période d\'indisponibilité.');
        }

        return $this->redirectToRoute('centre_emplacements_detail', ['id' => $id]);
    }

    /**
     * Supprimer une période d'indisponibilité
     */
    #[Route('/periode-indispo/{periodeId}/supprimer', name: 'supprimer_periode_indispo', methods: ['POST'], requirements: ['periodeId' => '\d+'])]
    public function supprimerPeriodeIndispo(
        int $periodeId,
        Request $request,
        PeriodeIndisponibiliteRepository $periodeRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $periode = $periodeRepo->find($periodeId);

        if (!$periode || $periode->getEmplacement()->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Période non trouvée');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_periode_' . $periodeId, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_emplacements_detail', ['id' => $periode->getEmplacement()->getId()]);
        }

        $emplacementId = $periode->getEmplacement()->getId();
        $entityManager->remove($periode);
        $entityManager->flush();

        $this->addFlash('success', 'Période d\'indisponibilité supprimée avec succès.');

        return $this->redirectToRoute('centre_emplacements_detail', ['id' => $emplacementId]);
    }

    /**
     * Méthode privée pour uploader une photo
     */
    private function uploadPhoto($photoFile, Emplacement $emplacement, SluggerInterface $slugger, EntityManagerInterface $entityManager): array
    {
        // Vérifier le type de fichier
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($photoFile->getMimeType(), $allowedMimeTypes)) {
            return ['success' => false, 'message' => 'Format de fichier non autorisé. Utilisez JPG, PNG ou WebP.'];
        }

        // Vérifier la taille (5MB max)
        if ($photoFile->getSize() > 5 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Le fichier est trop volumineux (5MB maximum).'];
        }

        $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

        try {
            $photoFile->move(
                $this->getParameter('kernel.project_dir') . '/public/uploads/photos',
                $newFilename
            );

            // Créer l'entité Photo
            $photo = new Photo();
            $photo->setCheminFichier($newFilename);
            $photo->setEmplacement($emplacement);
            $photo->setDateUpload(new \DateTime());

            // Définir l'ordre d'affichage
            $maxOrdre = 0;
            foreach ($emplacement->getPhotos() as $existingPhoto) {
                if ($existingPhoto->getOrdreAffichage() > $maxOrdre) {
                    $maxOrdre = $existingPhoto->getOrdreAffichage();
                }
            }
            $photo->setOrdreAffichage($maxOrdre + 1);

            $entityManager->persist($photo);

            return ['success' => true, 'photo' => $photo];
        } catch (FileException $e) {
            return ['success' => false, 'message' => 'Erreur lors de l\'upload de la photo.'];
        }
    }
}
