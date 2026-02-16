<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Repository\EmplacementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmplacementPublicController extends AbstractController
{
    /**
     * Afficher les détails publics d'un emplacement
     */
    #[Route('/emplacement/{id}', name: 'emplacement_detail', requirements: ['id' => '\d+'])]
    public function detail(
        int $id,
        EmplacementRepository $emplacementRepo
    ): Response {
        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement) {
            throw $this->createNotFoundException('Emplacement non trouvé');
        }

        // Vérifier que l'emplacement est actif
        if ($emplacement->getStatutAnnonce() !== 'active') {
            throw $this->createNotFoundException('Cet emplacement n\'est pas disponible');
        }

        // Vérifier si l'utilisateur est un locataire connecté
        $user = $this->getUser();
        $isLocataire = $user instanceof Locataire;

        return $this->render('public/emplacement_detail.html.twig', [
            'emplacement' => $emplacement,
            'isLocataire' => $isLocataire,
        ]);
    }
}
