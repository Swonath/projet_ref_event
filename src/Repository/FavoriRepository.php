<?php

namespace App\Repository;

use App\Entity\Favori;
use App\Entity\Locataire;
use App\Entity\Emplacement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favori>
 */
class FavoriRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favori::class);
    }

    /**
     * Vérifie si un emplacement est en favori pour un locataire
     */
    public function isFavori(Locataire $locataire, Emplacement $emplacement): bool
    {
        return $this->count([
            'locataire' => $locataire,
            'emplacement' => $emplacement
        ]) > 0;
    }

    /**
     * Trouve le favori pour le supprimer
     */
    public function findFavori(Locataire $locataire, Emplacement $emplacement): ?Favori
    {
        return $this->findOneBy([
            'locataire' => $locataire,
            'emplacement' => $emplacement
        ]);
    }

    /**
     * Compte le nombre de favoris pour un locataire
     */
    public function countByLocataire(Locataire $locataire): int
    {
        return $this->count(['locataire' => $locataire]);
    }

    /**
     * Récupère tous les favoris d'un locataire, triés par date d'ajout
     */
    public function findByLocataireOrderedByDate(Locataire $locataire): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.locataire = :locataire')
            ->setParameter('locataire', $locataire)
            ->orderBy('f.dateAjout', 'DESC')
            ->getQuery()
            ->getResult();
    }
}