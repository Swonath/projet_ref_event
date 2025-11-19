<?php

namespace App\Controller;

use App\Entity\Locataire;
use App\Entity\CentreCommercial;
use App\Form\LocataireRegistrationType;
use App\Form\CentreCommercialRegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

class RegistrationController extends AbstractController
{
    /**
     * Page d'inscription - Choix du type de compte
     */
    #[Route('/inscription', name: 'app_register')]
    public function index(): Response
    {
        return $this->render('registration/index.html.twig');
    }

    /**
     * Inscription d'un locataire (particulier ou entreprise)
     */
    #[Route('/inscription/locataire', name: 'app_register_locataire')]
    public function registerLocataire(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        // 1. CRÉER UN NOUVEAU LOCATAIRE VIDE
        $locataire = new Locataire();
        
        // 2. CRÉER LE FORMULAIRE
        $form = $this->createForm(LocataireRegistrationType::class, $locataire);
        
        // 3. TRAITER LA SOUMISSION DU FORMULAIRE
        $form->handleRequest($request);
        
        // 4. VÉRIFIER SI LE FORMULAIRE EST SOUMIS
        if ($form->isSubmitted()) {
            
            // 4a. SI LE FORMULAIRE N'EST PAS VALIDE, AFFICHER LES ERREURS
            if (!$form->isValid()) {
                // Récupérer et afficher toutes les erreurs
                foreach ($form->all() as $field) {
                    foreach ($field->getErrors() as $error) {
                        /** @var \Symfony\Component\Form\FormError $error */
                        $this->addFlash('error', $error->getMessage());
                    }
                }
                
                // Réafficher le formulaire avec les erreurs
                return $this->render('registration/locataire.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
            
            // 5. VÉRIFIER SI L'EMAIL EXISTE DÉJÀ
            $existingUser = $entityManager->getRepository(Locataire::class)
                ->findOneBy(['email' => $locataire->getEmail()]);
            
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('registration/locataire.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
            
            // 6. CHIFFRER LE MOT DE PASSE
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($locataire, $plainPassword);
            $locataire->setPassword($hashedPassword);
            
            // 7. DÉFINIR LE STATUT DU COMPTE
            $locataire->setStatutCompte('actif');
            
            // 8. SAUVEGARDER EN BASE DE DONNÉES
            try {
                $entityManager->persist($locataire);
                $entityManager->flush();
                
                // 9. MESSAGE DE CONFIRMATION ET REDIRECTION
                $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
                
            } catch (\Exception $e) {
                // En cas d'erreur lors de la sauvegarde
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte : ' . $e->getMessage());
            }
        }
        
        // 10. AFFICHER LE FORMULAIRE (première visite)
        return $this->render('registration/locataire.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    /**
     * Inscription d'un centre commercial
     */
    #[Route('/inscription/centre', name: 'app_register_centre')]
    public function registerCentre(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        // 1. CRÉER UN NOUVEAU CENTRE COMMERCIAL VIDE
        $centre = new CentreCommercial();
        
        // 2. CRÉER LE FORMULAIRE
        $form = $this->createForm(CentreCommercialRegistrationType::class, $centre);
        
        // 3. TRAITER LA SOUMISSION DU FORMULAIRE
        $form->handleRequest($request);
        
        // 4. VÉRIFIER SI LE FORMULAIRE EST SOUMIS
        if ($form->isSubmitted()) {
            
            // 4a. SI LE FORMULAIRE N'EST PAS VALIDE, AFFICHER LES ERREURS
            if (!$form->isValid()) {
                // Récupérer et afficher toutes les erreurs
                foreach ($form->all() as $field) {
                    foreach ($field->getErrors() as $error) {
                        /** @var \Symfony\Component\Form\FormError $error */
                        $this->addFlash('error', $error->getMessage());
                    }
                }
                
                return $this->render('registration/centre.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
            
            // 5. VÉRIFIER SI L'EMAIL EXISTE DÉJÀ
            $existingUser = $entityManager->getRepository(CentreCommercial::class)
                ->findOneBy(['email' => $centre->getEmail()]);
            
            if ($existingUser) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->render('registration/centre.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
            
            // 6. CHIFFRER LE MOT DE PASSE
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($centre, $plainPassword);
            $centre->setPassword($hashedPassword);
            
            // 7. DÉFINIR LE STATUT DU COMPTE (en attente de validation par un admin)
            $centre->setStatutCompte('en_attente');
            
            // 8. SAUVEGARDER EN BASE DE DONNÉES
            try {
                $entityManager->persist($centre);
                $entityManager->flush();
                
                // 9. MESSAGE DE CONFIRMATION ET REDIRECTION
                $this->addFlash('success', 'Votre demande d\'inscription a été envoyée. Un administrateur doit valider votre compte avant que vous puissiez vous connecter.');
                return $this->redirectToRoute('app_login');
                
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la création du compte : ' . $e->getMessage());
            }
        }
        
        // 10. AFFICHER LE FORMULAIRE
        return $this->render('registration/centre.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}