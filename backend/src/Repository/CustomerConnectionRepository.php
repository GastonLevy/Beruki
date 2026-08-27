<?php

namespace App\Repository;

use App\Entity\CustomerConnection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerConnection>
 */
class CustomerConnectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerConnection::class);
    }

    public function findOneByServiceIp(string $serviceIp): ?CustomerConnection
    {
        return $this->findOneBy(['serviceIp' => $serviceIp]);
    }
}
