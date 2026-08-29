<?php

namespace App\EventListener;

use App\Entity\Customer;
use App\Service\CustomerCodeGenerator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
final class CustomerCodeAssigner
{
    public function __construct(
        private readonly CustomerCodeGenerator $customerCodeGenerator,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Customer || $entity->getCustomerCode() !== null) {
            return;
        }

        $entity->assignCustomerCode($this->customerCodeGenerator->generateUnique());
    }
}
