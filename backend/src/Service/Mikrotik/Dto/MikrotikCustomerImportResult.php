<?php

namespace App\Service\Mikrotik\Dto;

final readonly class MikrotikCustomerImportResult
{
    public function __construct(
        public int $queuesRead,
        public int $created,
        public int $existing,
        public int $invalid,
        public int $ambiguous,
        public int $plansDiscovered,
        public int $newPlans,
        public int $existingPlans,
        public int $customerPlansToCreate,
        public int $ipAddressesToUpdate,
        public int $plansToUpdate,
        public int $macAddressesFound,
        public int $macAddressesToComplete,
        public int $macAddressesToUpdate,
        public bool $dryRun,
    ) {
    }
}
