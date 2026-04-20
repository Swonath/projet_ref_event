<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Entity\CentreCommercial;
use App\Repository\AvisRepository;
use App\Repository\EmplacementRepository;
use App\Repository\CentreCommercialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomePageController extends AbstractController
{
    /**
     * Page d'accueil avec recherche d'emplacements
     */
    #[Route('/', name: 'app_home_page')]
    public function index(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): Response {
        // Récupérer les paramètres de recherche
        $search = $request->query->get('search', '');
        $dateDebut = $request->query->get('date_debut', '');
        $dateFin = $request->query->get('date_fin', '');
        $surfaceMin = $request->query->get('surface_min', '');
        $surfaceMax = $request->query->get('surface_max', '');
        $prixMin = $request->query->get('prix_min', '');
        $prixMax = $request->query->get('prix_max', '');
        $type = $request->query->get('type', '');

        // Construire la requête de base
        $qb = $emplacementRepo->createQueryBuilder('e')
            ->join('e.centreCommercial', 'c')
            ->where('e.statutAnnonce = :statut')
            ->setParameter('statut', 'active')
            ->orderBy('e.dateCreation', 'DESC');

        // Appliquer les filtres de recherche
        if (!empty($search)) {
            $qb->andWhere('
                e.titreAnnonce LIKE :search
                OR e.description LIKE :search
                OR e.typeEmplacement LIKE :search
                OR c.nomCentre LIKE :search
                OR c.ville LIKE :search
                OR c.codePostal LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        // Filtres avancés
        if (!empty($surfaceMin)) {
            $qb->andWhere('e.surface >= :surfaceMin')
               ->setParameter('surfaceMin', (float) $surfaceMin);
        }

        if (!empty($surfaceMax)) {
            $qb->andWhere('e.surface <= :surfaceMax')
               ->setParameter('surfaceMax', (float) $surfaceMax);
        }

        if (!empty($prixMin)) {
            $qb->andWhere('e.tarifJour >= :prixMin')
               ->setParameter('prixMin', (float) $prixMin);
        }

        if (!empty($prixMax)) {
            $qb->andWhere('e.tarifJour <= :prixMax')
               ->setParameter('prixMax', (float) $prixMax);
        }

        if (!empty($type)) {
            $qb->andWhere('e.typeEmplacement = :type')
               ->setParameter('type', $type);
        }

        // Récupérer les 50 premiers résultats
        $emplacements = $qb->setMaxResults(50)
            ->getQuery()
            ->getResult();

        // Compter le total pour savoir s'il y a plus de résultats
        $totalCount = $emplacementRepo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.statutAnnonce = :statut')
            ->setParameter('statut', 'active');

        if (!empty($search)) {
            $totalCount->join('e.centreCommercial', 'c')
                ->andWhere('
                    e.titreAnnonce LIKE :search
                    OR e.description LIKE :search
                    OR e.typeEmplacement LIKE :search
                    OR c.nomCentre LIKE :search
                    OR c.ville LIKE :search
                    OR c.codePostal LIKE :search
                ')
                ->setParameter('search', '%' . $search . '%');
        }

        $total = $totalCount->getQuery()->getSingleScalarResult();

        // Récupérer les 10 emplacements les plus populaires (les plus réservés)
        $emplacementsPopulaires = $emplacementRepo->createQueryBuilder('e')
            ->leftJoin('e.reservations', 'r')
            ->where('e.statutAnnonce = :statut')
            ->setParameter('statut', 'active')
            ->groupBy('e.id')
            ->orderBy('COUNT(r.id)', 'DESC')
            ->addOrderBy('e.dateCreation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('home_page/index.html.twig', [
            'emplacements' => $emplacements,
            'total' => $total,
            'search' => $search,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'surfaceMin' => $surfaceMin,
            'surfaceMax' => $surfaceMax,
            'prixMin' => $prixMin,
            'prixMax' => $prixMax,
            'type' => $type,
            'emplacementsPopulaires' => $emplacementsPopulaires,
        ]);
    }

    /**
     * Charger plus d'emplacements (AJAX)
     */
    #[Route('/emplacements/charger-plus', name: 'emplacements_charger_plus', methods: ['GET'])]
    public function chargerPlus(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): Response {
        $offset = (int) $request->query->get('offset', 0);
        $search = $request->query->get('search', '');
        $surfaceMin = $request->query->get('surface_min', '');
        $surfaceMax = $request->query->get('surface_max', '');
        $prixMin = $request->query->get('prix_min', '');
        $prixMax = $request->query->get('prix_max', '');
        $type = $request->query->get('type', '');

        $qb = $emplacementRepo->createQueryBuilder('e')
            ->join('e.centreCommercial', 'c')
            ->where('e.statutAnnonce = :statut')
            ->setParameter('statut', 'active')
            ->orderBy('e.dateCreation', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(50);

        if (!empty($search)) {
            $qb->andWhere('
                e.titreAnnonce LIKE :search
                OR e.description LIKE :search
                OR e.typeEmplacement LIKE :search
                OR c.nomCentre LIKE :search
                OR c.ville LIKE :search
                OR c.codePostal LIKE :search
            ')
            ->setParameter('search', '%' . $search . '%');
        }

        // Filtres avancés
        if (!empty($surfaceMin)) {
            $qb->andWhere('e.surface >= :surfaceMin')
               ->setParameter('surfaceMin', (float) $surfaceMin);
        }

        if (!empty($surfaceMax)) {
            $qb->andWhere('e.surface <= :surfaceMax')
               ->setParameter('surfaceMax', (float) $surfaceMax);
        }

        if (!empty($prixMin)) {
            $qb->andWhere('e.tarifJour >= :prixMin')
               ->setParameter('prixMin', (float) $prixMin);
        }

        if (!empty($prixMax)) {
            $qb->andWhere('e.tarifJour <= :prixMax')
               ->setParameter('prixMax', (float) $prixMax);
        }

        if (!empty($type)) {
            $qb->andWhere('e.typeEmplacement = :type')
               ->setParameter('type', $type);
        }

        $emplacements = $qb->getQuery()->getResult();

        return $this->render('home_page/_emplacements_grid.html.twig', [
            'emplacements' => $emplacements,
        ]);
    }

    /**
     * Redirection intelligente vers le dashboard approprié
     * Utilisé quand l'utilisateur clique sur son avatar/compte
     */
    #[Route('/mon-compte', name: 'app_my_account')]
    public function myAccount(): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        // Si pas connecté, rediriger vers la page de connexion
        if (!$user) {
            $this->addFlash('info', 'Veuillez vous connecter pour accéder à votre compte.');
            return $this->redirectToRoute('app_login');
        }
        
        // Rediriger selon le type d'utilisateur
        
        // Si c'est un Locataire
        if ($user instanceof Locataire) {
            return $this->redirectToRoute('locataire_dashboard');
        }
        
        // Si c'est un Centre Commercial
        if ($user instanceof CentreCommercial) {
            return $this->redirectToRoute('centre_dashboard');
        }
        
        // Si c'est un Admin (vérifier le rôle)
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        
        // Par défaut (ne devrait jamais arriver), rediriger vers login
        $this->addFlash('error', 'Type de compte non reconnu.');
        return $this->redirectToRoute('app_login');
    }

    /**
     * Page de recherche avec carte interactive
     */
    /**
     * Sauvegarde les coordonnées GPS d'un centre (appelé depuis le JS après géocodage Nominatim)
     */
    #[Route('/centre/{id}/coordonnees', name: 'centre_save_coords', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function saveCentreCoords(
        int $id,
        Request $request,
        CentreCommercialRepository $centreRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $centre = $centreRepo->find($id);
        if (!$centre) {
            return new JsonResponse(['ok' => false], 404);
        }

        $data = json_decode($request->getContent(), true);
        $lat = isset($data['lat']) ? (float) $data['lat'] : null;
        $lng = isset($data['lng']) ? (float) $data['lng'] : null;

        if ($lat && $lng) {
            $centre->setLatitude($lat);
            $centre->setLongitude($lng);
            $em->flush();
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/recherche-carte', name: 'recherche_carte')]
    public function rechercheCarte(
        EmplacementRepository $emplacementRepo,
        CentreCommercialRepository $centreRepo,
        AvisRepository $avisRepo
    ): Response {
        // Récupérer tous les centres actifs ayant au moins un emplacement actif
        $centres = $centreRepo->createQueryBuilder('c')
            ->join('c.emplacements', 'e')
            ->where('c.statutCompte = :statut')
            ->andWhere('e.statutAnnonce = :emplacementStatut')
            ->setParameter('statut', 'actif')
            ->setParameter('emplacementStatut', 'active')
            ->groupBy('c.id')
            ->orderBy('c.nomCentre', 'ASC')
            ->getQuery()
            ->getResult();

        // Notes par centre
        $notesCentres = [];
        foreach ($centres as $centre) {
            $notesCentres[$centre->getId()] = $avisRepo->getStatsCentre($centre->getId());
        }

        // Récupérer les emplacements actifs (pour la sidebar)
        $emplacements = $emplacementRepo->createQueryBuilder('e')
            ->join('e.centreCommercial', 'c')
            ->where('e.statutAnnonce = :statut')
            ->setParameter('statut', 'active')
            ->orderBy('e.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('home_page/carte.html.twig', [
            'centres'      => $centres,
            'emplacements' => $emplacements,
            'notesCentres' => $notesCentres,
        ]);
    }
}