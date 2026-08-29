<?php

namespace App\Repository;

use App\Entity\Plan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Plan>
 */
class PlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Plan::class);
    }

    public function findEquivalentMikrotikPlan(string $mikrotikRateKey): ?Plan
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.mikrotikRateKey = :mikrotikRateKey OR (p.mikrotikRateKey IS NULL AND p.name = :mikrotikRateKey)')
            ->setParameter('mikrotikRateKey', $mikrotikRateKey)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
