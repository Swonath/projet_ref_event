<?php

namespace App\Service;

use App\Entity\Emplacement;
use App\Repository\ReservationRepository;
use App\Repository\PeriodeIndisponibiliteRepository;
use App\Enum\StatutReservation;

class ReservationCalculatorService
{
    public function __construct(
        private readonly ReservationRepository $reservationRepo,
        private readonly PeriodeIndisponibiliteRepository $periodeIndispoRepo
    ) {
    }

    /**
     * Calculer le prix total d'une réservation avec commission et TVA
     *
     * @param Emplacement $emplacement
     * @param \DateTime $dateDebut
     * @param \DateTime $dateFin
     * @return array{
     *     days: int,
     *     montantLocation: float,
     *     montantCommission: float,
     *     subtotalHT: float,
     *     montantTVA: float,
     *     montantTotal: float,
     *     caution: float,
     *     montantTotalAvecCaution: float
     * }
     */
    public function calculateTotalPrice(
        Emplacement $emplacement,
        \DateTime $dateDebut,
        \DateTime $dateFin
    ): array {
        // Calculer le nombre de jours
        $interval = $dateDebut->diff($dateFin);
        $days = (int) $interval->days;

        if ($days <= 0) {
            throw new \InvalidArgumentException('La date de fin doit être après la date de début');
        }

        // Calculer le montant de location HT selon la durée
        $montantLocationHT = $this->calculateBasePriceForDuration(
            $days,
            (float) $emplacement->getTarifJour(),
            $emplacement->getTarifSemaine() ? (float) $emplacement->getTarifSemaine() : null,
            $emplacement->getTarifMois() ? (float) $emplacement->getTarifMois() : null
        );

        // Calculer la commission (25%)
        $montantCommission = $montantLocationHT * 0.25;

        // Sous-total HT (location + commission)
        $subtotalHT = $montantLocationHT + $montantCommission;

        // Calculer la TVA (20%)
        $montantTVA = $subtotalHT * 0.20;

        // Montant total TTC
        $montantTotal = $subtotalHT + $montantTVA;

        // Caution
        $caution = $emplacement->getCaution() ? (float) $emplacement->getCaution() : 0.0;

        // Montant total avec caution
        $montantTotalAvecCaution = $montantTotal + $caution;

        return [
            'days' => $days,
            'montantLocation' => round($montantLocationHT, 2),
            'montantCommission' => round($montantCommission, 2),
            'subtotalHT' => round($subtotalHT, 2),
            'montantTVA' => round($montantTVA, 2),
            'montantTotal' => round($montantTotal, 2),
            'caution' => round($caution, 2),
            'montantTotalAvecCaution' => round($montantTotalAvecCaution, 2),
        ];
    }

    /**
     * Calculer le prix de base selon la durée et les tarifs disponibles
     *
     * @param int $days
     * @param float $tarifJour
     * @param float|null $tarifSemaine
     * @param float|null $tarifMois
     * @return float
     */
    private function calculateBasePriceForDuration(
        int $days,
        float $tarifJour,
        ?float $tarifSemaine,
        ?float $tarifMois
    ): float {
        // Durée de 30 jours ou plus: utiliser tarif mensuel si disponible
        if ($days >= 30 && $tarifMois !== null) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;

            $price = $months * $tarifMois;

            // Pour les jours restants, utiliser le tarif hebdomadaire ou journalier
            if ($remainingDays > 0) {
                if ($remainingDays >= 7 && $tarifSemaine !== null) {
                    $weeks = floor($remainingDays / 7);
                    $extraDays = $remainingDays % 7;
                    $price += ($weeks * $tarifSemaine) + ($extraDays * $tarifJour);
                } else {
                    $price += $remainingDays * $tarifJour;
                }
            }

            return $price;
        }

        // Durée de 7 à 29 jours: utiliser tarif hebdomadaire si disponible
        if ($days >= 7 && $tarifSemaine !== null) {
            $weeks = floor($days / 7);
            $remainingDays = $days % 7;

            return ($weeks * $tarifSemaine) + ($remainingDays * $tarifJour);
        }

        // Durée de 1 à 6 jours: utiliser tarif journalier
        return $days * $tarifJour;
    }

    /**
     * Vérifier si une plage de dates est disponible pour un emplacement
     *
     * @param Emplacement $emplacement
     * @param \DateTime $dateDebut
     * @param \DateTime $dateFin
     * @param int|null $excludeReservationId ID de réservation à exclure (pour modification)
     * @return bool
     */
    public function isDateRangeAvailable(
        Emplacement $emplacement,
        \DateTime $dateDebut,
        \DateTime $dateFin,
        ?int $excludeReservationId = null
    ): bool {
        // Vérifier les contraintes de durée si elles existent
        if (!$this->checkDurationConstraints($emplacement, $dateDebut, $dateFin)) {
            return false;
        }

        // Vérifier les périodes d'indisponibilité
        if ($this->hasConflictWithUnavailablePeriods($emplacement, $dateDebut, $dateFin)) {
            return false;
        }

        // Vérifier les réservations existantes
        if ($this->hasConflictWithReservations($emplacement, $dateDebut, $dateFin, $excludeReservationId)) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier les contraintes de durée min/max
     *
     * @param Emplacement $emplacement
     * @param \DateTime $dateDebut
     * @param \DateTime $dateFin
     * @return bool
     */
    private function checkDurationConstraints(
        Emplacement $emplacement,
        \DateTime $dateDebut,
        \DateTime $dateFin
    ): bool {
        $days = (int) $dateDebut->diff($dateFin)->days;

        // Vérifier durée minimum
        if ($emplacement->getDureeMinLocation() !== null && $days < $emplacement->getDureeMinLocation()) {
            return false;
        }

        // Vérifier durée maximum
        if ($emplacement->getDureeMaxLocation() !== null && $days > $emplacement->getDureeMaxLocation()) {
            return false;
        }

        return true;
    }

    /**
     * Vérifier les conflits avec les périodes d'indisponibilité
     *
     * @param Emplacement $emplacement
     * @param \DateTime $dateDebut
     * @param \DateTime $dateFin
     * @return bool
     */
    private function hasConflictWithUnavailablePeriods(
        Emplacement $emplacement,
        \DateTime $dateDebut,
        \DateTime $dateFin
    ): bool {
        $periodesIndispo = $this->periodeIndispoRepo->findBy(['emplacement' => $emplacement]);

        foreach ($periodesIndispo as $periode) {
            if ($this->dateRangesOverlap(
                $dateDebut,
                $dateFin,
                $periode->getDateDebut(),
                $periode->getDateFin()
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier les conflits avec les réservations existantes
     *
     * @param Emplacement $emplacement
     * @param \DateTime $dateDebut
     * @param \DateTime $dateFin
     * @param int|null $excludeReservationId
     * @return bool
     */
    private function hasConflictWithReservations(
        Emplacement $emplacement,
        \DateTime $dateDebut,
        \DateTime $dateFin,
        ?int $excludeReservationId = null
    ): bool {
        // Récupérer les réservations qui ne sont pas annulées ou refusées
        $qb = $this->reservationRepo->createQueryBuilder('r')
            ->where('r.emplacement = :emplacement')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('emplacement', $emplacement)
            ->setParameter('statuts', [
                StatutReservation::EN_ATTENTE,
                StatutReservation::VALIDEE,
                StatutReservation::EN_COURS,
            ]);

        // Exclure une réservation spécifique si nécessaire (pour modification)
        if ($excludeReservationId !== null) {
            $qb->andWhere('r.id != :excludeId')
               ->setParameter('excludeId', $excludeReservationId);
        }

        $reservations = $qb->getQuery()->getResult();

        foreach ($reservations as $reservation) {
            if ($this->dateRangesOverlap(
                $dateDebut,
                $dateFin,
                $reservation->getDateDebut(),
                $reservation->getDateFin()
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifier si deux plages de dates se chevauchent
     *
     * @param \DateTime $start1
     * @param \DateTime $end1
     * @param \DateTime $start2
     * @param \DateTime $end2
     * @return bool
     */
    private function dateRangesOverlap(
        \DateTime $start1,
        \DateTime $end1,
        \DateTime $start2,
        \DateTime $end2
    ): bool {
        // Deux plages se chevauchent si :
        // - Le début de la première est avant la fin de la seconde
        // - ET le début de la seconde est avant la fin de la première
        return $start1 < $end2 && $start2 < $end1;
    }

    /**
     * Obtenir les périodes indisponibles pour un emplacement (format pour JavaScript)
     *
     * @param Emplacement $emplacement
     * @return array
     */
    public function getUnavailablePeriods(Emplacement $emplacement): array
    {
        $unavailablePeriods = [];

        // Ajouter les périodes d'indisponibilité définies
        $periodesIndispo = $this->periodeIndispoRepo->findBy(['emplacement' => $emplacement]);
        foreach ($periodesIndispo as $periode) {
            $unavailablePeriods[] = [
                'from' => $periode->getDateDebut()->format('Y-m-d'),
                'to' => $periode->getDateFin()->format('Y-m-d'),
            ];
        }

        // Ajouter les périodes déjà réservées
        $qb = $this->reservationRepo->createQueryBuilder('r')
            ->where('r.emplacement = :emplacement')
            ->andWhere('r.statut IN (:statuts)')
            ->setParameter('emplacement', $emplacement)
            ->setParameter('statuts', [
                StatutReservation::EN_ATTENTE,
                StatutReservation::VALIDEE,
                StatutReservation::EN_COURS,
            ]);

        $reservations = $qb->getQuery()->getResult();
        foreach ($reservations as $reservation) {
            $unavailablePeriods[] = [
                'from' => $reservation->getDateDebut()->format('Y-m-d'),
                'to' => $reservation->getDateFin()->format('Y-m-d'),
            ];
        }

        return $unavailablePeriods;
    }
}
