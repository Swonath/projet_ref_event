<?php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Repository\CentreCommercialRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CentreCommercialPublicController extends AbstractController
{
    #[Route('/centre/{id}', name: 'centre_profil_public', requirements: ['id' => '\d+'])]
    public function profil(int $id, CentreCommercialRepository $centreRepo, AvisRepository $avisRepo): Response
    {
        $centre = $centreRepo->find($id);

        if (!$centre || $centre->getStatutCompte() !== 'actif') {
            throw $this->createNotFoundException('Centre commercial non trouvé');
        }

        // Emplacements actifs du centre
        $emplacements = $centre->getEmplacements()->filter(
            fn($e) => $e->getStatutAnnonce() === 'active'
        );

        // Note globale du centre
        $statsCentre = $avisRepo->getStatsCentre($centre->getId());

        // Note par emplacement
        $statsEmplacements = [];
        foreach ($emplacements as $emplacement) {
            $statsEmplacements[$emplacement->getId()] = $avisRepo->getStatsEmplacement($emplacement->getId());
        }

        return $this->render('public/centre_profil.html.twig', [
            'centre'           => $centre,
            'emplacements'     => $emplacements,
            'statsCentre'      => $statsCentre,
            'statsEmplacements' => $statsEmplacements,
        ]);
    }
}
