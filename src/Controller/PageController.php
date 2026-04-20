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
            // Vérification CSRF
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('contact_form', $submittedToken)) {
                $error = 'Token de sécurité invalide. Veuillez réessayer.';
            } else {
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
                        $emailMessage = (new Email())
                            ->from(new \Symfony\Component\Mime\Address('lanathaud@gmail.com', $nom))
                            ->replyTo(new \Symfony\Component\Mime\Address($email, $nom))
                            ->to('lanathaud@gmail.com')
                            ->subject($sujet)
                            ->text(sprintf("De : %s <%s>\n\n%s", $nom, $email, $message))
                            ->html(sprintf(
                                '<p style="color:#888;font-size:13px;">De : <strong>%s</strong> &lt;%s&gt;</p><hr style="border:none;border-top:1px solid #eee;margin:12px 0;"><p style="font-size:15px;line-height:1.7;">%s</p>',
                                htmlspecialchars($nom),
                                htmlspecialchars($email),
                                nl2br(htmlspecialchars($message))
                            ));

                        $mailer->send($emailMessage);
                        $this->addFlash('success', 'Votre message a bien été envoyé ! Nous vous répondrons dans les plus brefs délais.');
                        return $this->redirectToRoute('page_contact');
                    } catch (\Exception) {
                        $error = 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.';
                    }
                }
            }
        }

        return $this->render('pages/contact.html.twig', [
            'success' => $success,
            'error' => $error,
        ]);
    }
}
