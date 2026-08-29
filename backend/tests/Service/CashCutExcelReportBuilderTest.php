<?php

namespace App\Tests\Service;

use App\Entity\CashCut;
use App\Entity\Customer;
use App\Entity\CustomerPayment;
use App\Entity\User;
use App\Service\CashCutExcelReportBuilder;
use PHPUnit\Framework\TestCase;

final class CashCutExcelReportBuilderTest extends TestCase
{
    public function testBuildsReportOnlyFromClosedCashCutsProvided(): void
    {
        $user = (new User())->setUsername('collector');
        $customer = (new Customer())
            ->setFullName('Closed Customer')
            ->setSubscriberNumber('A-001');
        $this->setId($customer, 10);

        $payment = (new CustomerPayment())
            ->setCustomer($customer)
            ->setUser($user)
            ->setAmount('1500.00')
            ->setPaidAt(new \DateTimeImmutable('2026-08-10 09:30:00'));

        $cashCut = (new CashCut())
            ->setUser($user)
            ->setTotalAmount('1500.00')
            ->setUserCommissionAmount('150.00')
            ->setUserCommissionPercentage('10.00')
            ->setAmountToWithdraw('1350.00')
            ->setPaymentsCount(1)
            ->setClosedAt(new \DateTimeImmutable('2026-08-10 18:00:00'))
            ->addCustomerPayment($payment);
        $this->setId($cashCut, 25);

        $openPayment = (new CustomerPayment())
            ->setCustomer((new Customer())->setFullName('Open Customer'))
            ->setUser($user)
            ->setAmount('9999.00')
            ->setPaidAt(new \DateTimeImmutable('2026-08-10 10:00:00'));

        $report = (new CashCutExcelReportBuilder())->build([$cashCut]);
        $worksheets = $this->readWorksheets($report);

        self::assertStringStartsWith("PK\x03\x04", $report);
        self::assertStringContainsString('Cantidad de cortes cerrados', $worksheets);
        self::assertStringContainsString('<v>1</v>', $worksheets);
        self::assertStringContainsString('Total recaudado', $worksheets);
        self::assertStringContainsString('<v>1500</v>', $worksheets);
        self::assertStringContainsString('Total retirado', $worksheets);
        self::assertStringContainsString('<v>1350</v>', $worksheets);
        self::assertStringContainsString('Saldo neto', $worksheets);
        self::assertStringContainsString('<v>150</v>', $worksheets);
        self::assertStringContainsString('ID del corte', $worksheets);
        self::assertStringContainsString('<v>25</v>', $worksheets);
        self::assertStringContainsString('2026-08-10 18:00:00', $worksheets);
        self::assertStringContainsString('Closed Customer', $worksheets);
        self::assertStringNotContainsString((string) $openPayment->getAmount(), $worksheets);
        self::assertStringNotContainsString('Open Customer', $worksheets);
    }

    private function readWorksheets(string $report): string
    {
        $path = tempnam(sys_get_temp_dir(), 'beruki-report-test-');
        self::assertIsString($path);
        file_put_contents($path, $report);

        $zip = new \ZipArchive();
        self::assertTrue($zip->open($path));

        $worksheets = '';

        for ($index = 1; $index <= 3; $index++) {
            $worksheet = $zip->getFromName(sprintf('xl/worksheets/sheet%d.xml', $index));
            self::assertIsString($worksheet);
            $worksheets .= $worksheet;
        }

        $zip->close();
        @unlink($path);

        return $worksheets;
    }

    private function setId(object $entity, int $id): void
    {
        $property = new \ReflectionProperty($entity, 'id');
        $property->setValue($entity, $id);
    }
}
