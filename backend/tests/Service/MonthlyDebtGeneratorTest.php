<?php

namespace App\Tests\Service;

use App\Repository\CustomerRepository;
use App\Service\MonthlyDebtGenerator;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class MonthlyDebtGeneratorTest extends TestCase
{
    public function testReturnsMonthlyDebtGenerationStats(): void
    {
        $repository = new class extends CustomerRepository {
            public function __construct()
            {
            }

            public function countActive(): int
            {
                return 3;
            }

            public function countActiveWithMonthlyDebt(): int
            {
                return 1;
            }

            public function activateMonthlyDebtForActiveWithoutDebt(): int
            {
                return 2;
            }
        };

        $logger = new class extends AbstractLogger {
            /**
             * @var array<string, mixed>
             */
            public array $context = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->context = $context;
            }
        };

        $result = (new MonthlyDebtGenerator($repository, $logger))->generate('admin');

        self::assertSame([
            'processed' => 3,
            'activated' => 2,
            'alreadyInDebt' => 1,
        ], $result);
        self::assertSame('admin', $logger->context['executedBy']);
    }
}
