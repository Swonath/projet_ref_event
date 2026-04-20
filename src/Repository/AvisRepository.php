<?php

namespace App\Repository;

use App\Entity\Avis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Avis>
 */
class AvisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Avis::class);
    }

    /**
     * Note moyenne + nombre d'avis publiés pour un centre commercial
     * (via Avis → Reservation → Emplacement → CentreCommercial)
     *
     * @return array{moyenne: float|null, count: int}
     */
    public function getStatsCentre(int $centreId): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.noteGlobale) as moyenne, COUNT(a.id) as count')
            ->join('a.reservation', 'r')
            ->join('r.emplacement', 'e')
            ->join('e.centreCommercial', 'c')
            ->where('c.id = :centreId')
            ->andWhere('a.estPublie = true')
            ->setParameter('centreId', $centreId)
            ->getQuery()
            ->getSingleResult();

        return [
            'moyenne' => $result['moyenne'] !== null ? round((float) $result['moyenne'], 1) : null,
            'count'   => (int) $result['count'],
        ];
    }

    /**
     * Note moyenne + nombre d'avis publiés pour un emplacement
     *
     * @return array{moyenne: float|null, count: int}
     */
    public function getStatsEmplacement(int $emplacementId): array
    {
        $result = $this->createQueryBuilder('a')
            ->select('AVG(a.noteGlobale) as moyenne, COUNT(a.id) as count')
            ->join('a.reservation', 'r')
            ->join('r.emplacement', 'e')
            ->where('e.id = :emplacementId')
            ->andWhere('a.estPublie = true')
            ->setParameter('emplacementId', $emplacementId)
            ->getQuery()
            ->getSingleResult();

        return [
            'moyenne' => $result['moyenne'] !== null ? round((float) $result['moyenne'], 1) : null,
            'count'   => (int) $result['count'],
        ];
    }
}
