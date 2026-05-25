<?php

namespace App\Repository;

use App\Entity\CustomerPayment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerPayment>
 */
class CustomerPaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerPayment::class);
    }

    public function hasPaymentForCurrentMonth(int $customerId): bool
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        $payment = $this->createQueryBuilder('payment')
            ->select('payment.id')
            ->andWhere('payment.customer = :customerId')
            ->andWhere('payment.paidAt BETWEEN :startOfMonth AND :endOfMonth')
            ->setParameter('customerId', $customerId)
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $payment !== null;
    }
}