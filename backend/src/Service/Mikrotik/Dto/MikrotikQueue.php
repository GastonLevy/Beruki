<?php

namespace App\Service\Mikrotik\Dto;

final readonly class MikrotikQueue
{
    public function __construct(
        public string $serviceIp,
        public bool $disabled,
        public string $downloadRate,
        public string $uploadRate,
        public string $planKey,
    ) {
    }

    public function isActive(): bool
    {
        return !$this->disabled;
    }
}
