<?php

namespace App\Repository;

use App\Entity\CashCut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CashCut>
 */
class CashCutRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CashCut::class);
    }

    public function findHistoryWithFilters(
        ?string $username = null,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('cashCut')
            ->addSelect('user')
            ->join('cashCut.user', 'user')
            ->orderBy('cashCut.closedAt', 'DESC');

        if ($username !== null && trim($username) !== '') {
            $queryBuilder
                ->andWhere('LOWER(user.username) LIKE :username')
                ->setParameter('username', '%' . strtolower(trim($username)) . '%');
        }

        if ($dateFrom !== null) {
            $queryBuilder
                ->andWhere('cashCut.closedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder
                ->andWhere('cashCut.closedAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CashCut[]
     */
    public function findClosedReportCashCuts(
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null
    ): array {
        $queryBuilder = $this->createQueryBuilder('cashCut')
            ->addSelect('user')
            ->addSelect('payment')
            ->addSelect('customer')
            ->join('cashCut.user', 'user')
            ->leftJoin('cashCut.customerPayments', 'payment')
            ->leftJoin('payment.customer', 'customer')
            ->andWhere('cashCut.closedAt IS NOT NULL')
            ->orderBy('cashCut.closedAt', 'ASC')
            ->addOrderBy('payment.paidAt', 'ASC');

        if ($dateFrom !== null) {
            $queryBuilder
                ->andWhere('cashCut.closedAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder
                ->andWhere('cashCut.closedAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }

        return $queryBuilder
            ->getQuery()
            ->getResult();
    }
}
