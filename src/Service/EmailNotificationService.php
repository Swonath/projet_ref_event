<?php

namespace App\Service;

use App\Entity\Reservation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class EmailNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig
    ) {
    }

    /**
     * Notifie le centre commercial qu'une nouvelle réservation a été créée
     */
    public function notifierNouvelleReservation(Reservation $reservation): void
    {
        $centre = $reservation->getEmplacement()->getCentreCommercial();
        $locataire = $reservation->getLocataire();

        $html = $this->twig->render('emails/reservation_creee.html.twig', [
            'reservation' => $reservation,
            'centre' => $centre,
            'locataire' => $locataire,
        ]);

        $email = (new Email())
            ->from('noreply@refevent.fr')
            ->to($centre->getEmail())
            ->subject('Nouvelle demande de réservation - ' . $reservation->getEmplacement()->getTitreAnnonce())
            ->html($html);

        $this->envoyerEmail($email);

        // Confirmation au locataire
        $htmlLocataire = $this->twig->render('emails/reservation_confirmation_locataire.html.twig', [
            'reservation' => $reservation,
            'locataire' => $locataire,
        ]);

        $emailLocataire = (new Email())
            ->from('noreply@refevent.fr')
            ->to($locataire->getEmail())
            ->subject('Votre demande de réservation a bien été reçue - Références Événements')
            ->html($htmlLocataire);

        $this->envoyerEmail($emailLocataire);
    }

    /**
     * Notifie le locataire que sa réservation a été validée
     */
    public function notifierReservationValidee(Reservation $reservation): void
    {
        $locataire = $reservation->getLocataire();

        $html = $this->twig->render('emails/reservation_validee.html.twig', [
            'reservation' => $reservation,
            'locataire' => $locataire,
        ]);

        $email = (new Email())
            ->from('noreply@refevent.fr')
            ->to($locataire->getEmail())
            ->subject('Votre réservation a été validée - Références Événements')
            ->html($html);

        $this->envoyerEmail($email);
    }

    /**
     * Notifie le locataire que sa réservation a été refusée
     */
    public function notifierReservationRefusee(Reservation $reservation): void
    {
        $locataire = $reservation->getLocataire();

        $html = $this->twig->render('emails/reservation_refusee.html.twig', [
            'reservation' => $reservation,
            'locataire' => $locataire,
        ]);

        $email = (new Email())
            ->from('noreply@refevent.fr')
            ->to($locataire->getEmail())
            ->subject('Information concernant votre réservation - Références Événements')
            ->html($html);

        $this->envoyerEmail($email);
    }

    /**
     * Notifie les deux parties lors d'une annulation
     */
    public function notifierReservationAnnulee(Reservation $reservation, string $annuleePar): void
    {
        $locataire = $reservation->getLocataire();
        $centre = $reservation->getEmplacement()->getCentreCommercial();

        $html = $this->twig->render('emails/reservation_annulee.html.twig', [
            'reservation' => $reservation,
            'annuleePar' => $annuleePar,
        ]);

        // Notifier le locataire
        $emailLocataire = (new Email())
            ->from('noreply@refevent.fr')
            ->to($locataire->getEmail())
            ->subject('Annulation de réservation - Références Événements')
            ->html($html);
        $this->envoyerEmail($emailLocataire);

        // Notifier le centre
        $emailCentre = (new Email())
            ->from('noreply@refevent.fr')
            ->to($centre->getEmail())
            ->subject('Annulation de réservation - Références Événements')
            ->html($html);
        $this->envoyerEmail($emailCentre);
    }

    private function envoyerEmail(Email $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            // On log silencieusement pour ne pas bloquer le flux applicatif
        }
    }
}
