<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function countReservationsToday(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.createdAt >= :today')
            ->setParameter('today', new \DateTime('today'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getTodayRevenue(): float
    {
        // Définir une plage horaire pour toute la journée
        $startOfDay = new \DateTime('today midnight');
        $endOfDay = new \DateTime('tomorrow midnight'); // Le début de demain

        // Requête avec plage horaire pour couvrir toute la journée
        $revenue = $this->createQueryBuilder('r')
            ->select('SUM(r.totalPrice)')
            ->andWhere('r.createdAt >= :startOfDay')
            ->andWhere('r.createdAt < :endOfDay')
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->getQuery()
            ->getSingleScalarResult();

        // Si le résultat est null, on retourne 0.0
        return $revenue !== null ? (float) $revenue : 0.0;
    }

}
