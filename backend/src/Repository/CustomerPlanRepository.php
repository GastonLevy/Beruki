<?php

namespace App\Repository;

use App\Entity\Customer;
use App\Entity\CustomerPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerPlan>
 */
class CustomerPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerPlan::class);
    }

    public function findActiveOneByCustomerAndMikrotikRateKey(Customer $customer, string $mikrotikRateKey): ?CustomerPlan
    {
        return $this->createQueryBuilder('cp')
            ->innerJoin('cp.plan', 'p')
            ->andWhere('cp.customer = :customer')
            ->andWhere('cp.isActive = true')
            ->andWhere('p.mikrotikRateKey = :mikrotikRateKey')
            ->setParameter('customer', $customer)
            ->setParameter('mikrotikRateKey', $mikrotikRateKey)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByServiceIp(string $serviceIp): ?CustomerPlan
    {
        return $this->findOneBy(['serviceIp' => $serviceIp]);
    }

    public function findOneByMacAddress(string $macAddress): ?CustomerPlan
    {
        return $this->findOneBy(['macAddress' => $macAddress]);
    }

    /**
     * @return CustomerPlan[]
     */
    public function findActiveImportedByCustomer(Customer $customer): array
    {
        return $this->createQueryBuilder('cp')
            ->innerJoin('cp.plan', 'p')
            ->andWhere('cp.customer = :customer')
            ->andWhere('cp.isActive = true')
            ->andWhere('p.mikrotikRateKey IS NOT NULL')
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getResult();
    }
}
