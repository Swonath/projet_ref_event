<?php

namespace App\Controller\Admin;

use App\Repository\LocataireRepository;
use App\Repository\CentreCommercialRepository;
use App\Repository\ReservationRepository;
use App\Repository\EmplacementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractController
{
    /**
     * Dashboard principal admin
     */
    #[Route('', name: 'dashboard')]
    public function index(
        LocataireRepository $locataireRepo,
        CentreCommercialRepository $centreRepo,
        ReservationRepository $reservationRepo,
        EmplacementRepository $emplacementRepo
    ): Response {
        // Statistiques générales
        $stats = [
            'total_locataires' => $locataireRepo->count([]),
            'total_centres' => $centreRepo->count([]),
            'total_reservations' => $reservationRepo->count([]),
            'total_emplacements' => $emplacementRepo->count([]),
            'locataires_actifs' => $locataireRepo->count(['statutCompte' => 'actif']),
            'centres_actifs' => $centreRepo->count(['statutCompte' => 'actif']),
            'locataires_attente' => $locataireRepo->count(['statutCompte' => 'en_attente']),
            'centres_attente' => $centreRepo->count(['statutCompte' => 'en_attente']),
        ];

        // Dernières inscriptions (par ID décroissant)
        $derniersLocataires = $locataireRepo->findBy([], ['id' => 'DESC'], 10);
        $derniersCentres = $centreRepo->findBy([], ['id' => 'DESC'], 10);

        // Dernières réservations
        $dernieresReservations = $reservationRepo->findBy([], ['dateDemande' => 'DESC'], 10);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'derniersLocataires' => $derniersLocataires,
            'derniersCentres' => $derniersCentres,
            'dernieresReservations' => $dernieresReservations,
        ]);
    }

    /**
     * Gestion des locataires
     */
    #[Route('/locataires', name: 'locataires')]
    public function locataires(
        Request $request,
        LocataireRepository $locataireRepo
    ): Response {
        $filtre = $request->query->get('filtre', 'tous');

        $qb = $locataireRepo->createQueryBuilder('l')
            ->orderBy('l.id', 'DESC');

        if ($filtre === 'actifs') {
            $qb->where('l.statutCompte = :statut')
               ->setParameter('statut', 'actif');
        } elseif ($filtre === 'inactifs') {
            $qb->where('l.statutCompte != :statut')
               ->setParameter('statut', 'actif');
        }

        $locataires = $qb->getQuery()->getResult();

        return $this->render('admin/locataires.html.twig', [
            'locataires' => $locataires,
            'filtre' => $filtre,
        ]);
    }

    /**
     * Activer/désactiver un locataire
     */
    #[Route('/locataires/{id}/toggle-actif', name: 'locataires_toggle', methods: ['POST'])]
    public function toggleLocataire(
        int $id,
        Request $request,
        LocataireRepository $locataireRepo,
        EntityManagerInterface $em
    ): Response {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_locataire_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_locataires');
        }

        $locataire = $locataireRepo->find($id);
        if (!$locataire) {
            throw $this->createNotFoundException('Locataire non trouvé');
        }

        $nouveauStatut = ($locataire->getStatutCompte() === 'actif') ? 'en_attente' : 'actif';
        $locataire->setStatutCompte($nouveauStatut);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le compte de %s a été %s',
            $locataire->getNom(),
            $nouveauStatut === 'actif' ? 'activé' : 'désactivé'
        ));

        return $this->redirectToRoute('admin_locataires');
    }

    /**
     * Supprimer un locataire
     */
    #[Route('/locataires/{id}/supprimer', name: 'locataires_supprimer', methods: ['POST'])]
    public function supprimerLocataire(
        int $id,
        Request $request,
        LocataireRepository $locataireRepo,
        EntityManagerInterface $em
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_locataire_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_locataires');
        }

        $locataire = $locataireRepo->find($id);
        if (!$locataire) {
            throw $this->createNotFoundException('Locataire non trouvé');
        }

        // Vérifier qu'il n'y a pas de réservations actives
        $reservationsActives = $locataire->getReservations()->filter(function($reservation) {
            return in_array($reservation->getStatut(), ['en_attente', 'validee', 'en_cours']);
        });

        if (count($reservationsActives) > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce locataire : il a des réservations actives.');
            return $this->redirectToRoute('admin_locataires');
        }

        $nom = $locataire->getNom();
        $em->remove($locataire);
        $em->flush();

        $this->addFlash('success', sprintf('Le compte de %s a été supprimé', $nom));

        return $this->redirectToRoute('admin_locataires');
    }

    /**
     * Changer le mot de passe d'un locataire
     */
    #[Route('/locataires/{id}/changer-mot-de-passe', name: 'locataires_changer_mdp', methods: ['POST'])]
    public function changerMotDePasseLocataire(
        int $id,
        Request $request,
        LocataireRepository $locataireRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('changer_mdp_locataire_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_locataires');
        }

        $locataire = $locataireRepo->find($id);
        if (!$locataire) {
            throw $this->createNotFoundException('Locataire non trouvé');
        }

        $nouveauMdp = $request->request->get('nouveau_mdp');
        if (!$nouveauMdp || strlen($nouveauMdp) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
            return $this->redirectToRoute('admin_locataires');
        }

        $hashedPassword = $passwordHasher->hashPassword($locataire, $nouveauMdp);
        $locataire->setPassword($hashedPassword);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le mot de passe de %s a été modifié avec succès',
            $locataire->getNom()
        ));

        return $this->redirectToRoute('admin_locataires');
    }

    /**
     * Gestion des centres commerciaux
     */
    #[Route('/centres', name: 'centres')]
    public function centres(
        Request $request,
        CentreCommercialRepository $centreRepo
    ): Response {
        $filtre = $request->query->get('filtre', 'tous');

        $qb = $centreRepo->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        if ($filtre === 'actifs') {
            $qb->where('c.statutCompte = :statut')
               ->setParameter('statut', 'actif');
        } elseif ($filtre === 'inactifs') {
            $qb->where('c.statutCompte != :statut')
               ->setParameter('statut', 'actif');
        }

        $centres = $qb->getQuery()->getResult();

        return $this->render('admin/centres.html.twig', [
            'centres' => $centres,
            'filtre' => $filtre,
        ]);
    }

    /**
     * Activer/désactiver un centre
     */
    #[Route('/centres/{id}/toggle-actif', name: 'centres_toggle', methods: ['POST'])]
    public function toggleCentre(
        int $id,
        Request $request,
        CentreCommercialRepository $centreRepo,
        EntityManagerInterface $em
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle_centre_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_centres');
        }

        $centre = $centreRepo->find($id);
        if (!$centre) {
            throw $this->createNotFoundException('Centre non trouvé');
        }

        $nouveauStatut = ($centre->getStatutCompte() === 'actif') ? 'en_attente' : 'actif';
        $centre->setStatutCompte($nouveauStatut);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le compte de %s a été %s',
            $centre->getNomCentre(),
            $nouveauStatut === 'actif' ? 'activé' : 'désactivé'
        ));

        return $this->redirectToRoute('admin_centres');
    }

    /**
     * Supprimer un centre
     */
    #[Route('/centres/{id}/supprimer', name: 'centres_supprimer', methods: ['POST'])]
    public function supprimerCentre(
        int $id,
        Request $request,
        CentreCommercialRepository $centreRepo,
        EntityManagerInterface $em
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('supprimer_centre_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_centres');
        }

        $centre = $centreRepo->find($id);
        if (!$centre) {
            throw $this->createNotFoundException('Centre non trouvé');
        }

        // Vérifier qu'il n'y a pas d'emplacements avec réservations actives
        $hasActiveReservations = false;
        foreach ($centre->getEmplacements() as $emplacement) {
            $reservationsActives = $emplacement->getReservations()->filter(function($reservation) {
                return in_array($reservation->getStatut(), ['en_attente', 'validee', 'en_cours']);
            });
            if (count($reservationsActives) > 0) {
                $hasActiveReservations = true;
                break;
            }
        }

        if ($hasActiveReservations) {
            $this->addFlash('error', 'Impossible de supprimer ce centre : il a des emplacements avec des réservations actives.');
            return $this->redirectToRoute('admin_centres');
        }

        $nom = $centre->getNomCentre();
        $em->remove($centre);
        $em->flush();

        $this->addFlash('success', sprintf('Le compte de %s a été supprimé', $nom));

        return $this->redirectToRoute('admin_centres');
    }

    /**
     * Changer le mot de passe d'un centre commercial
     */
    #[Route('/centres/{id}/changer-mot-de-passe', name: 'centres_changer_mdp', methods: ['POST'])]
    public function changerMotDePasseCentre(
        int $id,
        Request $request,
        CentreCommercialRepository $centreRepo,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('changer_mdp_centre_' . $id, $token)) {
            $this->addFlash('error', 'Token CSRF invalide');
            return $this->redirectToRoute('admin_centres');
        }

        $centre = $centreRepo->find($id);
        if (!$centre) {
            throw $this->createNotFoundException('Centre non trouvé');
        }

        $nouveauMdp = $request->request->get('nouveau_mdp');
        if (!$nouveauMdp || strlen($nouveauMdp) < 6) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
            return $this->redirectToRoute('admin_centres');
        }

        $hashedPassword = $passwordHasher->hashPassword($centre, $nouveauMdp);
        $centre->setPassword($hashedPassword);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le mot de passe de %s a été modifié avec succès',
            $centre->getNomCentre()
        ));

        return $this->redirectToRoute('admin_centres');
    }

    /**
     * Vue d'ensemble des réservations
     */
    #[Route('/reservations', name: 'reservations')]
    public function reservations(
        Request $request,
        ReservationRepository $reservationRepo
    ): Response {
        $filtre = $request->query->get('filtre', 'toutes');

        $qb = $reservationRepo->createQueryBuilder('r')
            ->join('r.emplacement', 'e')
            ->join('r.locataire', 'l')
            ->orderBy('r.dateDemande', 'DESC');

        if ($filtre !== 'toutes') {
            $qb->where('r.statut = :statut')
               ->setParameter('statut', $filtre);
        }

        $reservations = $qb->getQuery()->getResult();

        return $this->render('admin/reservations.html.twig', [
            'reservations' => $reservations,
            'filtre' => $filtre,
        ]);
    }
}
