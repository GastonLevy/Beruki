<?php

namespace App\Service\Mikrotik\Dto;

use App\Entity\Plan;

final readonly class MikrotikPlanResolution
{
    public function __construct(
        public Plan $plan,
        public bool $isNew,
    ) {
    }
}
