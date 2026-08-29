<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function searchPaginated(
        string $search,
        int $limit,
        int $offset
    ): array {
        $qb = $this->createQueryBuilder('c');

        $qb
            ->andWhere('c.isArchived = :isArchived')
            ->setParameter('isArchived', false);

        if ($search !== '') {
            $qb
                ->andWhere('
                    c.fullName LIKE :search
                    OR c.email LIKE :search
                    OR c.subscriberNumber LIKE :search
                    OR c.customerCode LIKE :search
                ')
                ->setParameter(
                    'search',
                    '%' . $search . '%'
                );
        }

        $total = (clone $qb)
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $data = $qb
            ->orderBy('c.id', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return [
            'data' => $data,
            'total' => (int) $total,
        ];
    }

    public function findActiveById(int $id): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.isArchived = :isArchived')
            ->setParameter('id', $id)
            ->setParameter('isArchived', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveByCustomerCode(string $customerCode): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customerCode = :customerCode')
            ->andWhere('c.isArchived = :isArchived')
            ->setParameter('customerCode', $customerCode)
            ->setParameter('isArchived', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findOneByCustomerCode(string $customerCode): ?Customer
    {
        return $this->findOneBy(['customerCode' => $customerCode]);
    }

    public function existsByCustomerCode(string $customerCode): bool
    {
        return $this->findOneByCustomerCode($customerCode) instanceof Customer;
    }

    public function findActiveOneBy(array $criteria): ?Customer
    {
        $criteria['isArchived'] = false;

        return $this->findOneBy($criteria);
    }

    public function countActive(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isArchived = :isArchived')
            ->setParameter('isArchived', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActiveWithMonthlyDebt(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isArchived = :isArchived')
            ->andWhere('c.monthlyDebt = :monthlyDebt')
            ->setParameter('isArchived', false)
            ->setParameter('monthlyDebt', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function activateMonthlyDebtForActiveWithoutDebt(): int
    {
        return $this->createQueryBuilder('c')
            ->update()
            ->set('c.monthlyDebt', ':monthlyDebt')
            ->andWhere('c.isArchived = :isArchived')
            ->andWhere('(c.monthlyDebt != :monthlyDebt OR c.monthlyDebt IS NULL)')
            ->setParameter('monthlyDebt', true)
            ->setParameter('isArchived', false)
            ->getQuery()
            ->execute();
    }
}
