<?php

namespace App\Controller\Locataire;

use App\Entity\Reservation;
use App\Entity\Paiement;
use App\Entity\Locataire;
use App\Enum\StatutReservation;
use App\Form\ReservationType;
use App\Repository\EmplacementRepository;
use App\Repository\ReservationRepository;
use App\Service\StripeService;
use App\Service\ReservationCalculatorService;
use App\Service\EmailNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_LOCATAIRE')]
class ReservationController extends AbstractController
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly ReservationCalculatorService $calculatorService,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
        private readonly EmailNotificationService $emailNotification
    ) {
    }

    /**
     * Formulaire de sélection de dates pour réserver un emplacement
     */
    #[Route('/emplacement/{id}/reserver', name: 'locataire_reservation_new', methods: ['GET', 'POST'])]
    public function new(
        int $id,
        Request $request,
        EmplacementRepository $emplacementRepo
    ): Response {
        // Charger l'emplacement
        $emplacement = $emplacementRepo->find($id);

        if (!$emplacement || $emplacement->getStatutAnnonce() !== 'active') {
            $this->addFlash('error', 'Cet emplacement n\'est pas disponible à la réservation.');
            return $this->redirectToRoute('app_home_page');
        }

        // Créer le formulaire
        $form = $this->createForm(ReservationType::class, null, [
            'emplacement' => $emplacement,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateDebut = $form->get('dateDebut')->getData();
            $dateFin = $form->get('dateFin')->getData();

            // Calculer le prix
            try {
                $prices = $this->calculatorService->calculateTotalPrice(
                    $emplacement,
                    $dateDebut,
                    $dateFin
                );

                // Stocker en session
                $session = $request->getSession();
                $session->set('reservation_data', [
                    'emplacement_id' => $emplacement->getId(),
                    'date_debut' => $dateDebut->format('Y-m-d'),
                    'date_fin' => $dateFin->format('Y-m-d'),
                    'prices' => $prices,
                ]);

                // Rediriger vers le checkout
                return $this->redirectToRoute('locataire_reservation_checkout');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du calcul du prix. Veuillez réessayer.');
                $this->logger->error('Erreur calcul prix réservation', [
                    'error' => $e->getMessage(),
                    'emplacement_id' => $id,
                ]);
            }
        }

        // Récupérer les périodes indisponibles pour le JavaScript
        $unavailablePeriods = $this->calculatorService->getUnavailablePeriods($emplacement);

        return $this->render('locataire/reservation/form.html.twig', [
            'emplacement' => $emplacement,
            'form' => $form->createView(),
            'unavailablePeriods' => $unavailablePeriods,
        ]);
    }

    /**
     * Page de paiement (checkout) avec Stripe
     */
    #[Route('/reservation/checkout', name: 'locataire_reservation_checkout', methods: ['GET'])]
    public function checkout(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): Response {
        // Récupérer les données de session
        $session = $request->getSession();
        $reservationData = $session->get('reservation_data');

        if (!$reservationData) {
            $this->addFlash('error', 'Aucune réservation en cours. Veuillez recommencer.');
            return $this->redirectToRoute('app_home_page');
        }

        // Charger l'emplacement
        $emplacement = $emplacementRepo->find($reservationData['emplacement_id']);

        if (!$emplacement) {
            $session->remove('reservation_data');
            $this->addFlash('error', 'Emplacement introuvable.');
            return $this->redirectToRoute('app_home_page');
        }

        // Récupérer la clé publique Stripe
        $stripePublicKey = $_ENV['STRIPE_PUBLIC_KEY'] ?? '';

        return $this->render('locataire/reservation/checkout.html.twig', [
            'emplacement' => $emplacement,
            'dateDebut' => new \DateTime($reservationData['date_debut']),
            'dateFin' => new \DateTime($reservationData['date_fin']),
            'prices' => $reservationData['prices'],
            'stripe_public_key' => $stripePublicKey,
        ]);
    }

    /**
     * Créer un Payment Intent Stripe (AJAX)
     */
    #[Route('/reservation/payment-intent', name: 'locataire_reservation_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request): JsonResponse
    {
        // Vérifier le token CSRF
        $submittedToken = $request->headers->get('X-CSRF-TOKEN');
        if (!$this->isCsrfTokenValid('payment-intent', $submittedToken)) {
            return new JsonResponse(['error' => 'Token CSRF invalide'], 403);
        }

        // Récupérer les données de session
        $session = $request->getSession();
        $reservationData = $session->get('reservation_data');

        if (!$reservationData) {
            return new JsonResponse(['error' => 'Aucune réservation en cours'], 400);
        }

        try {
            /** @var Locataire $locataire */
            $locataire = $this->getUser();

            // Créer le Payment Intent
            $paymentIntent = $this->stripeService->createPaymentIntent(
                $reservationData['prices']['montantTotalAvecCaution'],
                [
                    'locataire_id' => $locataire->getId(),
                    'locataire_email' => $locataire->getEmail(),
                    'emplacement_id' => $reservationData['emplacement_id'],
                    'date_debut' => $reservationData['date_debut'],
                    'date_fin' => $reservationData['date_fin'],
                ]
            );

            return new JsonResponse([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur création Payment Intent', [
                'error' => $e->getMessage(),
                'locataire_id' => $this->getUser()->getId(),
            ]);

            return new JsonResponse([
                'error' => 'Une erreur est survenue. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * Confirmer la réservation après paiement réussi
     */
    #[Route('/reservation/confirm', name: 'locataire_reservation_confirm', methods: ['POST'])]
    public function confirm(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('confirm-reservation', $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_home_page');
        }

        // Récupérer le Payment Intent ID
        $paymentIntentId = $request->request->get('payment_intent_id');

        if (!$paymentIntentId) {
            $this->addFlash('error', 'Paiement invalide.');
            return $this->redirectToRoute('app_home_page');
        }

        // Récupérer les données de session
        $session = $request->getSession();
        $reservationData = $session->get('reservation_data');

        if (!$reservationData) {
            $this->addFlash('error', 'Session expirée. Veuillez recommencer.');
            return $this->redirectToRoute('app_home_page');
        }

        try {
            // Vérifier le paiement avec Stripe
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                $this->addFlash('error', 'Le paiement n\'a pas abouti. Veuillez réessayer.');
                return $this->redirectToRoute('locataire_reservation_checkout');
            }

            // Charger l'emplacement
            $emplacement = $emplacementRepo->find($reservationData['emplacement_id']);

            if (!$emplacement) {
                throw new \Exception('Emplacement introuvable');
            }

            // Créer les objets DateTime
            $dateDebut = new \DateTime($reservationData['date_debut']);
            $dateFin = new \DateTime($reservationData['date_fin']);

            // Double-check de la disponibilité (protection contre race condition)
            if (!$this->calculatorService->isDateRangeAvailable($emplacement, $dateDebut, $dateFin)) {
                // Les dates ne sont plus disponibles, créer un remboursement
                $this->stripeService->createRefund($paymentIntentId);

                $this->addFlash('error', 'Désolé, ces dates ont été réservées pendant votre paiement. Vous avez été remboursé automatiquement.');
                $session->remove('reservation_data');
                return $this->redirectToRoute('app_home_page');
            }

            /** @var Locataire $locataire */
            $locataire = $this->getUser();
            $prices = $reservationData['prices'];

            // Créer la réservation
            $reservation = new Reservation();
            $reservation->setLocataire($locataire);
            $reservation->setEmplacement($emplacement);
            $reservation->setDateDebut($dateDebut);
            $reservation->setDateFin($dateFin);
            $reservation->setMontantLocation((string) $prices['montantLocation']);
            $reservation->setMontantCommission((string) $prices['montantCommission']);
            $reservation->setMontantTotal((string) $prices['montantTotalAvecCaution']);
            $reservation->setCautionVersee((string) $prices['caution']);
            $reservation->setStatut(StatutReservation::EN_ATTENTE);
            $reservation->setDateDemande(new \DateTime());
            $reservation->setDatePaiement(new \DateTime());

            // Créer le paiement
            $paiement = new Paiement();
            $paiement->setReservation($reservation);
            $paiement->setMontant((string) $prices['montantTotalAvecCaution']);
            $paiement->setDatePaiement(new \DateTime());
            $paiement->setMethodePaiement('carte_bancaire');
            $paiement->setStatut('complete');
            $paiement->setTransactionId($paymentIntent->id);

            $reservation->setPaiement($paiement);

            // Sauvegarder en base de données
            $this->em->persist($reservation);
            $this->em->persist($paiement);
            $this->em->flush();

            // Nettoyer la session
            $session->remove('reservation_data');

            // Envoyer les notifications email
            $this->emailNotification->notifierNouvelleReservation($reservation);

            $this->addFlash('success', 'Votre réservation a été enregistrée avec succès ! Le centre commercial va maintenant valider votre demande.');

            return $this->redirectToRoute('locataire_reservation_detail', ['id' => $reservation->getId()]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur confirmation réservation', [
                'error' => $e->getMessage(),
                'payment_intent_id' => $paymentIntentId,
                'locataire_id' => $this->getUser()->getId(),
            ]);

            $this->addFlash('error', 'Une erreur est survenue lors de la confirmation. Veuillez contacter le support.');
            return $this->redirectToRoute('app_home_page');
        }
    }

    /**
     * Annuler le processus de réservation
     */
    #[Route('/reservation/cancel', name: 'locataire_reservation_cancel', methods: ['GET'])]
    public function cancel(Request $request): Response
    {
        // Nettoyer la session
        $session = $request->getSession();
        $session->remove('reservation_data');

        $this->addFlash('info', 'Votre réservation a été annulée. Aucun paiement n\'a été effectué.');

        return $this->redirectToRoute('app_home_page');
    }

    /**
     * Calculer le prix en AJAX (pour affichage temps réel)
     */
    #[Route('/reservation/calculate-price', name: 'locataire_reservation_calculate_price', methods: ['POST'])]
    public function calculatePrice(
        Request $request,
        EmplacementRepository $emplacementRepo
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $emplacementId = $data['emplacementId'] ?? null;
        $dateDebut = $data['dateDebut'] ?? null;
        $dateFin = $data['dateFin'] ?? null;

        if (!$emplacementId || !$dateDebut || !$dateFin) {
            return new JsonResponse(['error' => 'Données manquantes'], 400);
        }

        try {
            $emplacement = $emplacementRepo->find($emplacementId);

            if (!$emplacement) {
                return new JsonResponse(['error' => 'Emplacement introuvable'], 404);
            }

            $dateDebutObj = new \DateTime($dateDebut);
            $dateFinObj = new \DateTime($dateFin);

            // Vérifier la disponibilité
            $available = $this->calculatorService->isDateRangeAvailable(
                $emplacement,
                $dateDebutObj,
                $dateFinObj
            );

            if (!$available) {
                return new JsonResponse([
                    'available' => false,
                    'message' => 'Ces dates ne sont pas disponibles'
                ]);
            }

            // Calculer le prix
            $prices = $this->calculatorService->calculateTotalPrice(
                $emplacement,
                $dateDebutObj,
                $dateFinObj
            );

            return new JsonResponse([
                'available' => true,
                'prices' => $prices,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors du calcul du prix'
            ], 500);
        }
    }

    /**
     * Webhook Stripe pour gérer les événements de paiement
     * Cette route n'est pas protégée par IsGranted car elle est appelée par Stripe
     */
    #[Route('/stripe/webhook', name: 'stripe_webhook', methods: ['POST'])]
    public function webhook(
        Request $request,
        ReservationRepository $reservationRepo
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('Stripe-Signature');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

        try {
            $event = $this->stripeService->constructWebhookEvent($payload, $signature, $webhookSecret);

            // Traiter l'événement selon son type
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->logger->info('Payment Intent réussi', [
                        'payment_intent_id' => $event->data->object->id,
                    ]);
                    break;

                case 'payment_intent.payment_failed':
                    $this->logger->warning('Échec de paiement', [
                        'payment_intent_id' => $event->data->object->id,
                        'error' => $event->data->object->last_payment_error,
                    ]);
                    break;

                case 'charge.refunded':
                    $charge = $event->data->object;
                    $paymentIntentId = $charge->payment_intent;

                    // Trouver le paiement correspondant
                    $paiement = $this->em->getRepository(Paiement::class)
                        ->findOneBy(['transactionId' => $paymentIntentId]);

                    if ($paiement) {
                        $paiement->setDateRemboursement(new \DateTime());
                        $paiement->setMontantRembourse((string) ($charge->amount_refunded / 100));
                        $paiement->setStatut('rembourse');
                        $this->em->flush();

                        $this->logger->info('Remboursement traité', [
                            'payment_intent_id' => $paymentIntentId,
                            'montant' => $charge->amount_refunded / 100,
                        ]);
                    }
                    break;

                default:
                    $this->logger->info('Événement webhook Stripe non géré', [
                        'type' => $event->type,
                    ]);
            }

            return new Response('', 200);
        } catch (\Exception $e) {
            $this->logger->error('Erreur webhook Stripe', [
                'error' => $e->getMessage(),
            ]);

            return new Response('', 400);
        }
    }
}
