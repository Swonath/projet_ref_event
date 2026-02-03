<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/pages', name: 'page_')]
class PageController extends AbstractController
{
    /**
     * Page À propos
     */
    #[Route('/a-propos', name: 'about')]
    public function about(): Response
    {
        return $this->render('pages/about.html.twig');
    }

    /**
     * Page Mentions légales
     */
    #[Route('/mentions-legales', name: 'legal')]
    public function legal(): Response
    {
        return $this->render('pages/legal.html.twig');
    }

    /**
     * Page CGU (Conditions Générales d'Utilisation)
     */
    #[Route('/conditions-generales', name: 'terms')]
    public function terms(): Response
    {
        return $this->render('pages/terms.html.twig');
    }

    /**
     * Page Politique de confidentialité
     */
    #[Route('/confidentialite', name: 'privacy')]
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
    }

    /**
     * Page Contact
     */
    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $success = false;
        $error = null;

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $sujet = $request->request->get('sujet');
            $message = $request->request->get('message');

            // Validation basique
            if (empty($nom) || empty($email) || empty($sujet) || empty($message)) {
                $error = 'Tous les champs sont obligatoires.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Adresse email invalide.';
            } else {
                try {
                    // Créer et envoyer l'email
                    $emailMessage = (new Email())
                        ->from($email)
                        ->to('contact@references-evenements.fr')
                        ->subject('Contact: ' . $sujet)
                        ->html(sprintf(
                            '<p><strong>De:</strong> %s (%s)</p><p><strong>Sujet:</strong> %s</p><p><strong>Message:</strong></p><p>%s</p>',
                            htmlspecialchars($nom),
                            htmlspecialchars($email),
                            htmlspecialchars($sujet),
                            nl2br(htmlspecialchars($message))
                        ));

                    $mailer->send($emailMessage);
                    $success = true;
                } catch (\Exception $e) {
                    $error = 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.';
                }
            }
        }

        return $this->render('pages/contact.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
}
