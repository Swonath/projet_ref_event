<?php

namespace App\Controller\Locataire;

use App\Entity\Locataire;
use App\Enum\StatutReservation;
use App\Repository\ReservationRepository;
use App\Repository\EmplacementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/dashboard/locataire', name: 'locataire_')]
#[IsGranted('ROLE_LOCATAIRE')]
class DashboardController extends AbstractController
{
    /**
     * Page principale du dashboard locataire
     */
    #[Route('', name: 'dashboard')]
    public function index(ReservationRepository $reservationRepo): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // ✅ MODIFICATION : Afficher TOUTES les réservations (pas seulement les actives)
        $reservations = $reservationRepo->findBy(
            ['locataire' => $locataire],
            ['dateDebut' => 'DESC'], // Triées par date de début (plus récentes en premier)
            10  // Limiter à 10 réservations pour ne pas surcharger le dashboard
        );
        
        // Calculer tous les compteurs
        $stats = $this->calculateStats($reservationRepo, $locataire);
        
        $messages = [];
        
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
     */
    #[Route('/reservations', name: 'reservations')]
    public function reservations(
        Request $request,
        ReservationRepository $reservationRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer le filtre depuis l'URL (par défaut : toutes)
        $filtre = $request->query->get('filtre', 'toutes');
        
        $now = new \DateTime();
        
        // ✅ Construire la requête selon le filtre
        $qb = $reservationRepo->createQueryBuilder('r')
            ->where('r.locataire = :locataire')
            ->setParameter('locataire', $locataire);
        
        // Filtres par STATUT (depuis l'enum)
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
                // En cours = statut EN_COURS et dates actives
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
            
            // Filtres TEMPORELS (qui tiennent compte du statut)
            case 'actives':
                // Réservations en cours (dates actives + statuts actifs)
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
                // Réservations futures (dateDebut dans le futur + pas annulées/refusées)
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
                // Réservations passées (dateFin dans le passé + statut terminée)
                $qb->andWhere('r.dateFin < :now')
                   ->andWhere('r.statut = :statut')
                   ->setParameter('now', $now)
                   ->setParameter('statut', StatutReservation::TERMINEE)
                   ->orderBy('r.dateFin', 'DESC');
                break;
                
            default: // 'toutes'
                // Toutes les réservations, triées par date de début décroissante
                $qb->orderBy('r.dateDebut', 'DESC');
                break;
        }
        
        $reservations = $qb->getQuery()->getResult();
        
        // Calculer les stats pour afficher les compteurs
        $stats = $this->calculateStats($reservationRepo, $locataire);
        
        return $this->render('locataire/reservations.html.twig', [
            'locataire' => $locataire,
            'reservations' => $reservations,
            'filtre_actif' => $filtre,
            'stats' => $stats,
            'statuts' => StatutReservation::cases(),
        ]);
    }
    
    /**
     * Calcule les statistiques pour le dashboard et les filtres
     */
    private function calculateStats(ReservationRepository $reservationRepo, Locataire $locataire): array
    {
        $now = new \DateTime();
        $firstDayOfMonth = new \DateTime('first day of this month 00:00:00');
        $lastDayOfMonth = new \DateTime('last day of this month 23:59:59');
        
        // Total de toutes les réservations
        $totalReservations = $reservationRepo->count(['locataire' => $locataire]);
        
        // Compteurs par STATUT
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
        $stats['favoris'] = 0;
        $stats['depenses_mois'] = round($depensesMois, 2);
        
        return $stats;
    }
    
    /**
     * Détail d'une réservation
     */
    #[Route('/reservations/{id}', name: 'reservation_detail')]
    public function reservationDetail(
        int $id,
        ReservationRepository $reservationRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $reservation = $reservationRepo->find($id);
        
        if (!$reservation || $reservation->getLocataire() !== $locataire) {
            throw $this->createNotFoundException('Réservation non trouvée');
        }
        
        return $this->render('locataire/reservation_detail.html.twig', [
            'reservation' => $reservation,
        ]);
    }
    
    /**
     * Page "Mes favoris"
     */
    #[Route('/favoris', name: 'favoris')]
    public function favoris(EmplacementRepository $emplacementRepo): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $favoris = [];
        
        return $this->render('locataire/favoris.html.twig', [
            'locataire' => $locataire,
            'favoris' => $favoris,
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
        UserPasswordHasherInterface $passwordHasher
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
     * Page "Mes documents"
     */
    #[Route('/documents', name: 'documents')]
    public function documents(): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $documents = [];
        
        return $this->render('locataire/documents.html.twig', [
            'locataire' => $locataire,
            'documents' => $documents,
        ]);
    }
    
    /**
     * Page "Messages"
     */
    #[Route('/messages', name: 'messages')]
    public function messages(): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $conversations = [];
        
        return $this->render('locataire/messages.html.twig', [
            'conversations' => $conversations,
        ]);
    }
}