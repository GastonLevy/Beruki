<?php

namespace App\Service\Mikrotik\Dto;

final readonly class MikrotikQueueReadResult
{
    /**
     * @param MikrotikQueue[] $queues
     */
    public function __construct(
        public int $queuesRead,
        public array $queues,
        public int $invalidQueues,
    ) {
    }
}
