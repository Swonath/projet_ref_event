<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Entity\CentreCommercial;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomePageController extends AbstractController
{
    /**
     * Page d'accueil
     * Affiche la même page pour tout le monde, mais avec un header adapté
     */
    #[Route('/', name: 'app_home_page')]
    public function index(): Response
    {
        return $this->render('home_page/index.html.twig');
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
}