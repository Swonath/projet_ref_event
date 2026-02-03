<?php

namespace App\Controller\CentreCommercial;

use App\Entity\CentreCommercial;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard/centre/avis', name: 'centre_avis_')]
#[IsGranted('ROLE_CENTRE')]
class AvisController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(
        Request $request,
        AvisRepository $avisRepo
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $filtre = $request->query->get('filtre', 'tous');

        // Récupérer les avis via les réservations
        $qb = $avisRepo->createQueryBuilder('a')
            ->join('a.reservation', 'r')
            ->join('r.emplacement', 'e')
            ->where('e.centreCommercial = :centre')
            ->setParameter('centre', $centre)
            ->orderBy('a.dateCreation', 'DESC');

        // Appliquer le filtre
        switch ($filtre) {
            case 'publies':
                $qb->andWhere('a.estPublie = true');
                break;
            case 'non_publies':
                $qb->andWhere('a.estPublie = false');
                break;
            case 'avec_reponse':
                $qb->andWhere('a.reponse IS NOT NULL');
                break;
            case 'sans_reponse':
                $qb->andWhere('a.reponse IS NULL');
                break;
        }

        $avis = $qb->getQuery()->getResult();

        // Calculer les stats
        $stats = [
            'total' => count($avis),
            'moyenne' => 0,
            'publies' => 0,
            'avec_reponse' => 0,
        ];

        $sommeNotes = 0;
        foreach ($avis as $avisItem) {
            $sommeNotes += $avisItem->getNoteGlobale();
            if ($avisItem->isEstPublie()) {
                $stats['publies']++;
            }
            if ($avisItem->getReponse()) {
                $stats['avec_reponse']++;
            }
        }

        if ($stats['total'] > 0) {
            $stats['moyenne'] = round($sommeNotes / $stats['total'], 1);
            $stats['taux_reponse'] = round(($stats['avec_reponse'] / $stats['total']) * 100);
        } else {
            $stats['taux_reponse'] = 0;
        }

        return $this->render('centre_commercial/avis.html.twig', [
            'centre' => $centre,
            'avis' => $avis,
            'stats' => $stats,
            'filtre' => $filtre,
        ]);
    }

    #[Route('/{id}/repondre', name: 'repondre', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function repondre(
        int $id,
        Request $request,
        AvisRepository $avisRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $avis = $avisRepo->find($id);

        if (!$avis || $avis->getReservation()->getEmplacement()->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Avis non trouvé');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('repondre_avis_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_avis_index');
        }

        $reponse = $request->request->get('reponse');
        if (empty(trim($reponse))) {
            $this->addFlash('error', 'La réponse ne peut pas être vide.');
            return $this->redirectToRoute('centre_avis_index');
        }

        $avis->setReponse($reponse);
        $avis->setDateReponse(new \DateTime());

        $entityManager->flush();

        $this->addFlash('success', 'Réponse publiée avec succès.');

        return $this->redirectToRoute('centre_avis_index');
    }

    #[Route('/{id}/moderer', name: 'moderer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function moderer(
        int $id,
        Request $request,
        AvisRepository $avisRepo,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var CentreCommercial $centre */
        $centre = $this->getUser();

        $avis = $avisRepo->find($id);

        if (!$avis || $avis->getReservation()->getEmplacement()->getCentreCommercial() !== $centre) {
            throw $this->createNotFoundException('Avis non trouvé');
        }

        // Vérifier le token CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('moderer_avis_' . $id, $submittedToken)) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('centre_avis_index');
        }

        // Toggle estPublie
        $avis->setEstPublie(!$avis->isEstPublie());

        if ($avis->isEstPublie() && !$avis->getDatePublication()) {
            $avis->setDatePublication(new \DateTime());
        }

        $entityManager->flush();

        $message = $avis->isEstPublie() ? 'Avis publié.' : 'Avis masqué.';
        $this->addFlash('success', $message);

        return $this->redirectToRoute('centre_avis_index');
    }
}
