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
use Symfony\Component\HttpFoundation\Request;

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
        
        // Récupérer les 12 premiers favoris
        $favoris = $favoriRepo->createQueryBuilder('f')
            ->where('f.locataire = :locataire')
            ->setParameter('locataire', $locataire)
            ->orderBy('f.dateAjout', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
        
        // Compter le total de favoris
        $totalFavoris = $favoriRepo->count(['locataire' => $locataire]);
        
        return $this->render('locataire/favoris.html.twig', [
            'locataire' => $locataire,
            'favoris' => $favoris,
            'totalFavoris' => $totalFavoris,
        ]);
    }
    
    /**
     * Charger plus de favoris (AJAX)
     */
    #[Route('/charger-plus', name: 'charger_plus', methods: ['GET'])]
    public function chargerPlus(
        FavoriRepository $favoriRepo,
        Request $request
    ): JsonResponse {
        /** @var Locataire $locataire */
        $locataire = $this->getUser();
        
        $offset = $request->query->get('offset', 0);
        
        // Récupérer 12 favoris à partir de l'offset
        $favoris = $favoriRepo->createQueryBuilder('f')
            ->where('f.locataire = :locataire')
            ->setParameter('locataire', $locataire)
            ->orderBy('f.dateAjout', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();
        
        // Générer le HTML pour chaque favori
        $html = '';
        foreach ($favoris as $favori) {
            $emplacement = $favori->getEmplacement();
            $photo = $emplacement->getPhotos()->first();
            $photoUrl = $photo ? '/uploads/photos/' . $photo->getCheminFichier() : '';
            
            $html .= '<div class="reservation-card" data-favori-id="' . $favori->getId() . '">';
            $html .= '<div class="reservation-image">';
            
            if ($photoUrl) {
                $html .= '<img src="' . $photoUrl . '" alt="' . htmlspecialchars($emplacement->getTitreAnnonce()) . '">';
            } else {
                $html .= '<div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; background:#f5f5f5; color:#999;">📷</div>';
            }
            
            $html .= '</div>';
            $html .= '<div class="reservation-info">';
            $html .= '<h3>' . htmlspecialchars($emplacement->getTitreAnnonce()) . '</h3>';
            $html .= '<div class="reservation-details">';
            $html .= '📍 ' . htmlspecialchars($emplacement->getCentreCommercial()->getNomCentre()) . ' • ';
            $html .= htmlspecialchars($emplacement->getTypeEmplacement()) . ' • ';
            $html .= htmlspecialchars($emplacement->getSurface()) . ' m²';
            $html .= '</div>';
            $html .= '<div class="reservation-details">';
            $html .= '❤️ Ajouté le ' . $favori->getDateAjout()->format('d/m/Y');
            $html .= '</div>';
            $html .= '<span class="reservation-status status-disponible">✓ Disponible</span>';
            $html .= '</div>';
            $html .= '<div class="reservation-actions">';
            $html .= '<div class="reservation-price">' . $emplacement->getTarifJour() . '€/jour</div>';
            $html .= '<a href="/" class="btn-small btn-primary-small">Voir détails</a>';
            $html .= '<button class="btn-small btn-secondary-small" onclick="retirerDesFavoris(' . $emplacement->getId() . ', this)" title="Retirer des favoris">Retirer ❤️</button>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        return new JsonResponse([
            'success' => true,
            'html' => $html,
            'hasMore' => count($favoris) === 12
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
        
        // RÃ©cupÃ©rer l'emplacement
        $emplacement = $emplacementRepo->find($id);
        
        if (!$emplacement) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Emplacement non trouvÃ©'
            ], 404);
        }
        
        // VÃ©rifier si dÃ©jÃ  en favori
        if ($favoriRepo->isFavori($locataire, $emplacement)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Cet emplacement est dÃ©jÃ  dans vos favoris'
            ], 400);
        }
        
        // CrÃ©er le favori
        $favori = new Favori();
        $favori->setLocataire($locataire);
        $favori->setEmplacement($emplacement);
        
        $entityManager->persist($favori);
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Emplacement ajoutÃ© aux favoris',
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
        
        // RÃ©cupÃ©rer l'emplacement
        $emplacement = $emplacementRepo->find($id);
        
        if (!$emplacement) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Emplacement non trouvÃ©'
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
            'message' => 'Emplacement retirÃ© des favoris',
            'favoris_count' => $favoriRepo->countByLocataire($locataire)
        ]);
    }
}