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

    public function getPendingCollectionByUser(): array
    {
        return $this->createQueryBuilder('payment')
            ->select('user.id AS userId')
            ->addSelect('user.username AS username')
            ->addSelect('SUM(payment.amount) AS totalAmount')
            ->addSelect('COUNT(payment.id) AS paymentsCount')
            ->join('payment.user', 'user')
            ->andWhere('payment.cashCut IS NULL')
            ->groupBy('user.id')
            ->addGroupBy('user.username')
            ->orderBy('user.username', 'ASC')
            ->getQuery()
            ->getArrayResult();
    }

    public function findPendingPaymentsByUser(int $userId): array
    {
        return $this->createQueryBuilder('payment')
            ->andWhere('payment.user = :userId')
            ->andWhere('payment.cashCut IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findPendingPaymentsByUserWithCustomer(int $userId): array
    {
        return $this->createQueryBuilder('payment')
            ->addSelect('customer')
            ->join('payment.customer', 'customer')
            ->andWhere('payment.user = :userId')
            ->andWhere('payment.cashCut IS NULL')
            ->setParameter('userId', $userId)
            ->orderBy('payment.paidAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
