<?php

namespace App\Enum;

/**
 * Énumération des statuts possibles pour une réservation
 * 
 * Cette enum définit tous les statuts qu'une réservation peut avoir.
 * Utiliser une enum garantit qu'on ne peut pas mettre n'importe quelle valeur.
 */
enum StatutReservation: string
{
    /**
     * La réservation vient d'être créée et attend validation
     */
    case EN_ATTENTE = 'en_attente';
    
    /**
     * La réservation a été validée par le centre commercial
     */
    case VALIDEE = 'validee';
    
    /**
     * La réservation est en cours (période active)
     */
    case EN_COURS = 'en_cours';
    
    /**
     * La réservation est terminée (période passée)
     */
    case TERMINEE = 'terminee';
    
    /**
     * La réservation a été refusée par le centre commercial
     */
    case REFUSEE = 'refusee';
    
    /**
     * La réservation a été annulée (par le locataire ou le centre)
     */
    case ANNULEE = 'annulee';

    /**
     * Retourne le libellé français du statut
     * Utile pour l'affichage dans les templates
     */
    public function getLibelle(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::VALIDEE => 'Validée',
            self::EN_COURS => 'En cours',
            self::TERMINEE => 'Terminée',
            self::REFUSEE => 'Refusée',
            self::ANNULEE => 'Annulée',
        };
    }

    /**
     * Retourne la classe CSS correspondant au statut
     * Utile pour l'affichage avec les bonnes couleurs
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'en-attente',
            self::VALIDEE => 'validee',
            self::EN_COURS => 'en-cours',
            self::TERMINEE => 'terminee',
            self::REFUSEE => 'refusee',
            self::ANNULEE => 'annulee',
        };
    }

    /**
     * Retourne l'icône emoji correspondant au statut
     */
    public function getIcone(): string
    {
        return match($this) {
            self::EN_ATTENTE => '⏳',
            self::VALIDEE => '✅',
            self::EN_COURS => '🔄',
            self::TERMINEE => '✔️',
            self::REFUSEE => '❌',
            self::ANNULEE => '🚫',
        };
    }

    /**
     * Retourne tous les statuts possibles
     * Utile pour les formulaires (select)
     */
    public static function getChoices(): array
    {
        return [
            'En attente' => self::EN_ATTENTE,
            'Validée' => self::VALIDEE,
            'En cours' => self::EN_COURS,
            'Terminée' => self::TERMINEE,
            'Refusée' => self::REFUSEE,
            'Annulée' => self::ANNULEE,
        ];
    }
}