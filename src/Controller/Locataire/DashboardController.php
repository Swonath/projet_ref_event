<?php

namespace App\Controller\Locataire;

use App\Entity\Locataire;
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
    public function index(): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // TODO: Récupérer les vraies données depuis la base
        // Pour l'instant, on utilise des données de test
        
        $stats = [
            'reservations_actives' => 2,
            'messages_non_lus' => 5,
            'favoris' => 8,
            'depenses_mois' => 1850,
        ];
        
        // Réservations en cours (données de test)
        $reservations = [
            [
                'id' => 1,
                'emplacement' => 'Centre Atlantis',
                'ville' => 'Saint-Herblain',
                'type' => 'Kiosque',
                'surface' => '25m²',
                'date_debut' => new \DateTime('2025-11-01'),
                'date_fin' => new \DateTime('2025-11-15'),
                'statut' => 'En cours',
                'prix_total' => 2250,
            ],
            [
                'id' => 2,
                'emplacement' => 'Beaulieu',
                'ville' => 'Nantes Centre',
                'type' => 'Vitrine',
                'surface' => '15m²',
                'date_debut' => new \DateTime('2025-12-01'),
                'date_fin' => new \DateTime('2025-12-24'),
                'statut' => 'À venir',
                'prix_total' => 2880,
            ],
        ];
        
        // Messages récents (données de test)
        $messages = [
            [
                'expediteur' => 'Centre Atlantis',
                'contenu' => 'Votre demande de réservation a été validée',
                'date' => new \DateTime('-2 hours'),
                'lu' => false,
            ],
            [
                'expediteur' => 'Beaulieu',
                'contenu' => 'Documents à fournir pour finaliser votre réservation',
                'date' => new \DateTime('-1 day'),
                'lu' => false,
            ],
        ];
        
        return $this->render('locataire/dashboard.html.twig', [
            'locataire' => $locataire,
            'stats' => $stats,
            'reservations' => $reservations,
            'messages' => $messages,
        ]);
    }
    
    /**
     * Page "Mes réservations"
     */
    #[Route('/reservations', name: 'reservations')]
    public function reservations(
        Request $request,
        ReservationRepository $reservationRepo
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer le filtre depuis l'URL
        $filtre = $request->query->get('filtre', 'toutes');
        
        // TODO: Récupérer les vraies réservations depuis la base de données
        // Pour l'instant, on retourne un tableau vide
        $reservations = [];
        
        // Une fois que tu auras des données en base, remplace par :
        /*
        switch ($filtre) {
            case 'en_cours':
                $reservations = $reservationRepo->findBy(
                    ['locataire' => $locataire, 'statut' => 'en_cours'],
                    ['dateDebut' => 'DESC']
                );
                break;
            case 'a_venir':
                $reservations = $reservationRepo->findBy(
                    ['locataire' => $locataire, 'statut' => 'confirmee'],
                    ['dateDebut' => 'ASC']
                );
                break;
            case 'passees':
                $reservations = $reservationRepo->findBy(
                    ['locataire' => $locataire, 'statut' => 'terminee'],
                    ['dateFin' => 'DESC']
                );
                break;
            default:
                $reservations = $reservationRepo->findBy(
                    ['locataire' => $locataire],
                    ['dateDebut' => 'DESC']
                );
        }
        */
        
        return $this->render('locataire/reservations.html.twig', [
            'locataire' => $locataire,
            'reservations' => $reservations,
            'filtre_actif' => $filtre,
        ]);
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
        
        // Vérifier que la réservation existe et appartient au locataire
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
        
        // TODO: Récupérer les vrais favoris depuis la base
        // Pour l'instant, on retourne un tableau vide
        $favoris = [];
        
        // Une fois que tu auras créé la table Favori, remplace par :
        /*
        $favoris = $entityManager->getRepository(Favori::class)->findBy(
            ['locataire' => $locataire],
            ['dateAjout' => 'DESC']
        );
        */
        
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
     * Modifier le profil (ROUTE AJOUTÉE)
     */
    #[Route('/profil/modifier', name: 'profil_edit', methods: ['POST'])]
    public function profilEdit(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer les données du formulaire
        $locataire->setNom($request->request->get('nom'));
        $locataire->setEmail($request->request->get('email'));
        $locataire->setTelephone($request->request->get('telephone'));
        $locataire->setAdresseFacturation($request->request->get('adresse'));
        $locataire->setCodePostal($request->request->get('codePostal'));
        $locataire->setVille($request->request->get('ville'));
        
        // Informations professionnelles (si elles existent dans ton entité)
        if (method_exists($locataire, 'setSiret')) {
            $locataire->setSiret($request->request->get('siret'));
        }
        if (method_exists($locataire, 'setTypeActivite')) {
            $locataire->setTypeActivite($request->request->get('typeActivite'));
        }
        
        // Changer le mot de passe si un nouveau est fourni
        $nouveauMotDePasse = $request->request->get('nouveau_mot_de_passe');
        $confirmerMotDePasse = $request->request->get('confirmer_mot_de_passe');
        
        if (!empty($nouveauMotDePasse)) {
            // Vérifier que les deux mots de passe correspondent
            if ($nouveauMotDePasse !== $confirmerMotDePasse) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('locataire_profil');
            }
            
            // Vérifier la longueur minimale
            if (strlen($nouveauMotDePasse) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('locataire_profil');
            }
            
            // Hasher et définir le nouveau mot de passe
            $hashedPassword = $passwordHasher->hashPassword($locataire, $nouveauMotDePasse);
            $locataire->setPassword($hashedPassword);
        }
        
        // Sauvegarder les modifications
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
        
        // TODO: Récupérer tous les documents liés aux réservations
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
        
        // TODO: Implémenter la messagerie
        $conversations = [];
        
        return $this->render('locataire/messages.html.twig', [
            'conversations' => $conversations,
        ]);
    }
}