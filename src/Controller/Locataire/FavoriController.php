<?php

namespace App\Controller\Locataire;

use App\Entity\Favori;
use App\Entity\Locataire;
use App\Repository\FavoriRepository;
use App\Repository\EmplacementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/locataire/favoris', name: 'locataire_favoris_')]
#[IsGranted('ROLE_LOCATAIRE')]
class FavoriController extends AbstractController
{
    /**
     * Page "Mes favoris"
     */
    #[Route('', name: 'index')]
    public function index(FavoriRepository $favoriRepo): Response
    {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer tous les favoris du locataire
        $favoris = $favoriRepo->findByLocataireOrderedByDate($locataire);
        
        return $this->render('locataire/favoris.html.twig', [
            'locataire' => $locataire,
            'favoris' => $favoris,
        ]);
    }
    
    /**
     * Ajouter un emplacement aux favoris
     */
    #[Route('/ajouter/{id}', name: 'ajouter', methods: ['POST'])]
    public function ajouter(
        int $id,
        EmplacementRepository $emplacementRepo,
        FavoriRepository $favoriRepo,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer l'emplacement
        $emplacement = $emplacementRepo->find($id);
        
        if (!$emplacement) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Emplacement non trouvé'
            ], 404);
        }
        
        // Vérifier si déjà en favori
        if ($favoriRepo->isFavori($locataire, $emplacement)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet emplacement est déjà dans vos favoris'
            ], 400);
        }
        
        // Créer le favori
        $favori = new Favori();
        $favori->setLocataire($locataire);
        $favori->setEmplacement($emplacement);
        
        $entityManager->persist($favori);
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Emplacement ajouté aux favoris',
            'favoris_count' => $favoriRepo->countByLocataire($locataire)
        ]);
    }
    
    /**
     * Retirer un emplacement des favoris
     */
    #[Route('/retirer/{id}', name: 'retirer', methods: ['POST'])]
    public function retirer(
        int $id,
        EmplacementRepository $emplacementRepo,
        FavoriRepository $favoriRepo,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        // Récupérer l'emplacement
        $emplacement = $emplacementRepo->find($id);
        
        if (!$emplacement) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Emplacement non trouvé'
            ], 404);
        }
        
        // Trouver le favori
        $favori = $favoriRepo->findFavori($locataire, $emplacement);
        
        if (!$favori) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet emplacement n\'est pas dans vos favoris'
            ], 400);
        }
        
        // Supprimer le favori
        $entityManager->remove($favori);
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Emplacement retiré des favoris',
            'favoris_count' => $favoriRepo->countByLocataire($locataire)
        ]);
    }
}