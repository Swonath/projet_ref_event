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
     * Créer un Payment Intent pour un montant donné
     *
     * @param float $amount Montant en euros
     * @param array $metadata Métadonnées à attacher au paiement
     * @return PaymentIntent
     */
    public function createPaymentIntent(float $amount, array $metadata = []): PaymentIntent
    {
        // Stripe utilise les centimes, donc on multiplie par 100
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
     * Récupérer un Payment Intent existant
     *
     * @param string $paymentIntentId
     * @return PaymentIntent
     */
    public function retrievePaymentIntent(string $paymentIntentId): PaymentIntent
    {
        return PaymentIntent::retrieve($paymentIntentId);
    }

    /**
     * Créer un remboursement pour un paiement
     *
     * @param string $paymentIntentId L'ID du Payment Intent à rembourser
     * @param float|null $amount Montant à rembourser en euros (null = montant complet)
     * @return Refund
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
     *
     * @param string $payload Le corps de la requête webhook
     * @param string $signature La signature HTTP header
     * @param string $webhookSecret Le secret du webhook
     * @return Event
     * @throws SignatureVerificationException Si la signature est invalide
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
