<?php

namespace App\Tests\Repository;

use App\Repository\CustomerRepository;
use PHPUnit\Framework\TestCase;

class CustomerRepositoryMonthlyDebtTest extends TestCase
{
    public function testMonthlyDebtBulkUpdateTargetsOnlyActiveCustomersWithoutDebt(): void
    {
        $method = new \ReflectionMethod(CustomerRepository::class, 'activateMonthlyDebtForActiveWithoutDebt');
        $source = file_get_contents($method->getFileName());

        self::assertIsString($source);
        self::assertStringContainsString("->set('c.monthlyDebt', ':monthlyDebt')", $source);
        self::assertStringContainsString("c.isArchived = :isArchived", $source);
        self::assertStringContainsString("c.monthlyDebt != :monthlyDebt OR c.monthlyDebt IS NULL", $source);
        self::assertStringContainsString("->setParameter('isArchived', false)", $source);
        self::assertStringContainsString("->setParameter('monthlyDebt', true)", $source);
    }

    public function testMonthlyDebtStatsCountActiveAndAlreadyMarkedCustomers(): void
    {
        $source = file_get_contents((new \ReflectionClass(CustomerRepository::class))->getFileName());

        self::assertIsString($source);
        self::assertStringContainsString('public function countActive(): int', $source);
        self::assertStringContainsString('public function countActiveWithMonthlyDebt(): int', $source);
        self::assertStringContainsString("c.monthlyDebt = :monthlyDebt", $source);
    }
}
