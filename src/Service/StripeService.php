<?php

namespace App\Service;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;

class StripeService
{
    public function __construct(
        private readonly string $stripeSecretKey
    ) {
        Stripe::setApiKey($this->stripeSecretKey);
    }

    /**
     * Créer un Payment Intent standard (sans Stripe Connect)
     */
    public function createPaymentIntent(float $amount, array $metadata = []): PaymentIntent
    {
        $amountInCents = (int) ($amount * 100);

        return PaymentIntent::create([
            'amount' => $amountInCents,
            'currency' => 'eur',
            'metadata' => $metadata,
            'automatic_payment_methods' => [
                'enabled' => true,
            ],
        ]);
    }

    /**
     * Créer un Payment Intent avec transfert automatique vers un centre (Stripe Connect)
     *
     * La répartition est :
     *   - 25% reste sur le compte Références Événements (commission)
     *   - 75% est transféré vers le compte Stripe Connect du centre commercial
     *
     * Pour utiliser cette méthode, le centre commercial doit avoir un stripeAccountId
     * configuré dans son profil (obtenu via le processus d'onboarding Stripe Connect).
     *
     * @param float  $montantTotal       Montant total TTC facturé au locataire
     * @param float  $montantCommission  Montant de la commission (25%) à retenir
     * @param string $stripeAccountId    L'ID du compte Stripe Connect du centre (ex: "acct_xxx")
     * @param array  $metadata           Métadonnées Stripe
     */
    public function createPaymentIntentWithTransfer(
        float $montantTotal,
        float $montantCommission,
        string $stripeAccountId,
        array $metadata = []
    ): PaymentIntent {
        $totalCents = (int) ($montantTotal * 100);
        $commissionCents = (int) ($montantCommission * 100);
        $transferCents = $totalCents - $commissionCents; // 75% vers le centre

        return PaymentIntent::create([
            'amount' => $totalCents,
            'currency' => 'eur',
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
            // transfer_data : le montant qui sera transféré automatiquement
            // après succès du paiement vers le compte Connect du centre
            'transfer_data' => [
                'amount' => $transferCents,
                'destination' => $stripeAccountId,
            ],
        ]);
    }

    /**
     * Générer le lien d'onboarding Stripe Connect pour un centre commercial.
     * Ce lien est à envoyer au centre pour qu'il configure son compte Stripe.
     *
     * @param string $email       Email du centre commercial
     * @param string $returnUrl   URL de retour après onboarding réussi
     * @param string $refreshUrl  URL si le lien expire
     */
    public function createConnectOnboardingLink(
        string $email,
        string $returnUrl,
        string $refreshUrl
    ): string {
        // 1. Créer un compte Stripe Connect Express
        $account = \Stripe\Account::create([
            'type' => 'express',
            'email' => $email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'business_type' => 'company',
        ]);

        // 2. Générer le lien d'onboarding
        $accountLink = \Stripe\AccountLink::create([
            'account' => $account->id,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        // Retourner l'ID du compte pour le sauvegarder en base
        // ainsi que l'URL d'onboarding
        return json_encode([
            'account_id' => $account->id,
            'onboarding_url' => $accountLink->url,
        ]);
    }

    /**
     * Récupérer un Payment Intent existant
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Créer un remboursement pour un paiement
     */
    public function createRefund(string $paymentIntentId, ?float $amount = null): Refund
    {
        $params = [
            'payment_intent' => $paymentIntentId,
        ];

        if ($amount !== null) {
            $params['amount'] = (int) ($amount * 100);
        }

        return Refund::create($params);
    }

    /**
     * Construire et valider un événement webhook Stripe
     */
    public function constructWebhookEvent(string $payload, string $signature, string $webhookSecret): Event
    {
        return \Stripe\Webhook::constructEvent(
            $payload,
            $signature,
            $webhookSecret
        );
    }
}
