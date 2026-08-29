<?php

namespace App\Tests\Service\Mikrotik;

use App\Entity\Customer;
use App\Entity\CustomerPlan;
use App\Entity\Plan;
use App\Repository\CustomerPlanRepository;
use App\Repository\CustomerRepository;
use App\Repository\PlanRepository;
use App\Service\CustomerCodeGenerator;
use App\Service\Mikrotik\Dto\MikrotikQueue;
use App\Service\Mikrotik\Dto\MikrotikQueueReadResult;
use App\Service\Mikrotik\MikrotikCustomerImporter;
use App\Service\Mikrotik\MikrotikPlanResolver;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MikrotikCustomerImporterTest extends TestCase
{
    public function testExistingConnectionByIpWithoutMacGetsMacCompleted(): void
    {
        $plan = $this->createImportedPlan('100/50');
        $customerPlan = $this->createCustomerPlanWithCustomer($plan, '10.10.9.67');
        $entityManager = $this->createTransactionalEntityManager();

        $result = $this->createImporter(
            $this->createCustomerPlanRepository($customerPlan),
            $entityManager,
            [$plan]
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.67', false, '100/50', 'aa:bb:cc:dd:ee:ff'),
        ], 0));

        self::assertSame(1, $result->existing);
        self::assertSame(0, $result->created);
        self::assertSame(0, $result->customerPlansToCreate);
        self::assertSame(0, $result->ipAddressesToUpdate);
        self::assertSame(0, $result->plansToUpdate);
        self::assertSame(1, $result->macAddressesFound);
        self::assertSame(1, $result->macAddressesToComplete);
        self::assertSame(0, $result->macAddressesToUpdate);
        self::assertSame('aa:bb:cc:dd:ee:ff', $customerPlan->getMacAddress());
    }

    public function testExistingConnectionByIpWithDifferentMacGetsCurrentMac(): void
    {
        $plan = $this->createImportedPlan('100/50');
        $customerPlan = $this->createCustomerPlanWithCustomer($plan, '10.10.9.67', '11:22:33:44:55:66');
        $entityManager = $this->createTransactionalEntityManager();

        $result = $this->createImporter(
            $this->createCustomerPlanRepository($customerPlan),
            $entityManager,
            [$plan]
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.67', false, '100/50', 'aa:bb:cc:dd:ee:ff'),
        ], 0));

        self::assertSame(0, $result->ipAddressesToUpdate);
        self::assertSame(0, $result->plansToUpdate);
        self::assertSame(1, $result->macAddressesFound);
        self::assertSame(0, $result->macAddressesToComplete);
        self::assertSame(1, $result->macAddressesToUpdate);
        self::assertSame('aa:bb:cc:dd:ee:ff', $customerPlan->getMacAddress());
    }

    public function testExistingConnectionByIpWithoutMikrotikMacKeepsStoredMac(): void
    {
        $plan = $this->createImportedPlan('100/50');
        $customerPlan = $this->createCustomerPlanWithCustomer($plan, '10.10.9.67', '11:22:33:44:55:66');
        $entityManager = $this->createTransactionalEntityManager();

        $result = $this->createImporter(
            $this->createCustomerPlanRepository($customerPlan),
            $entityManager,
            [$plan]
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.67'),
        ], 0));

        self::assertSame(0, $result->ipAddressesToUpdate);
        self::assertSame(0, $result->plansToUpdate);
        self::assertSame(0, $result->macAddressesFound);
        self::assertSame(0, $result->macAddressesToComplete);
        self::assertSame(0, $result->macAddressesToUpdate);
        self::assertSame('11:22:33:44:55:66', $customerPlan->getMacAddress());
    }

    public function testConnectionFoundByMacGetsNewIpAndSyncedFields(): void
    {
        $oldPlan = $this->createImportedPlan('100/50');
        $newPlan = $this->createImportedPlan('200/100');
        $customerPlan = $this->createCustomerPlanWithCustomer($oldPlan, '10.10.9.67', 'aa:bb:cc:dd:ee:ff');
        $entityManager = $this->createTransactionalEntityManager();

        $result = $this->createImporter(
            $this->createCustomerPlanRepository(null, $customerPlan),
            $entityManager,
            [$oldPlan, $newPlan]
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.99', true, '200/100', 'aa:bb:cc:dd:ee:ff'),
        ], 0));

        self::assertSame(1, $result->existing);
        self::assertSame(1, $result->ipAddressesToUpdate);
        self::assertSame(1, $result->plansToUpdate);
        self::assertSame('10.10.9.99', $customerPlan->getServiceIp());
        self::assertSame($newPlan, $customerPlan->getPlan());
        self::assertFalse($customerPlan->isActive());
    }

    public function testCreatesCustomerAndConnectionWhenIpAndMacAreNew(): void
    {
        $persistedCustomers = [];
        $persistedCustomerPlans = [];
        $plan = $this->createImportedPlan('100/50');
        $entityManager = $this->createTransactionalEntityManager($persistedCustomers, $persistedCustomerPlans);
        $customerRepository = $this->createMock(CustomerRepository::class);
        $customerRepository
            ->method('existsByCustomerCode')
            ->willReturn(false);

        $result = $this->createImporter(
            $this->createCustomerPlanRepository(),
            $entityManager,
            [$plan],
            $customerRepository
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.67', false, '100/50', 'aa:bb:cc:dd:ee:ff'),
        ], 0));

        self::assertSame(1, $result->created);
        self::assertSame(0, $result->existing);
        self::assertSame(1, $result->customerPlansToCreate);
        self::assertSame(0, $result->ipAddressesToUpdate);
        self::assertSame(0, $result->plansToUpdate);
        self::assertSame(1, $result->macAddressesFound);
        self::assertSame(0, $result->macAddressesToComplete);
        self::assertSame(0, $result->macAddressesToUpdate);
        self::assertCount(1, $persistedCustomers);
        self::assertCount(1, $persistedCustomerPlans);
        self::assertSame('10.10.9.67', $persistedCustomerPlans[0]->getServiceIp());
        self::assertSame('aa:bb:cc:dd:ee:ff', $persistedCustomerPlans[0]->getMacAddress());
        self::assertSame($plan, $persistedCustomerPlans[0]->getPlan());
    }

    public function testIpAndMacPointingToDifferentConnectionsAreReportedAsConflict(): void
    {
        $plan = $this->createImportedPlan('100/50');
        $planByIp = $this->createCustomerPlanWithCustomer($plan, '10.10.9.67', '11:22:33:44:55:66');
        $planByMac = $this->createCustomerPlanWithCustomer($plan, '10.10.9.99', 'aa:bb:cc:dd:ee:ff');
        $entityManager = $this->createTransactionalEntityManager();
        $entityManager->expects(self::never())->method('persist');

        $result = $this->createImporter(
            $this->createCustomerPlanRepository($planByIp, $planByMac),
            $entityManager,
            [$plan]
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.67', false, '100/50', 'aa:bb:cc:dd:ee:ff'),
        ], 0));

        self::assertSame(1, $result->ambiguous);
        self::assertSame(0, $result->existing);
        self::assertSame(0, $result->ipAddressesToUpdate);
        self::assertSame(0, $result->plansToUpdate);
        self::assertSame(1, $result->macAddressesFound);
        self::assertSame(0, $result->macAddressesToComplete);
        self::assertSame(0, $result->macAddressesToUpdate);
        self::assertSame('11:22:33:44:55:66', $planByIp->getMacAddress());
        self::assertSame('10.10.9.99', $planByMac->getServiceIp());
    }

    public function testDryRunDoesNotPersistOrModifyExistingConnection(): void
    {
        $oldPlan = $this->createImportedPlan('100/50');
        $newPlan = $this->createImportedPlan('200/100');
        $customerPlan = $this->createCustomerPlanWithCustomer($oldPlan, '10.10.9.67', '11:22:33:44:55:66');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');
        $entityManager->expects(self::never())->method('wrapInTransaction');

        $result = $this->createImporter(
            $this->createCustomerPlanRepository($customerPlan),
            $entityManager,
            [$oldPlan, $newPlan]
        )->import(new MikrotikQueueReadResult(1, [
            $this->queue('10.10.9.67', true, '200/100', 'aa:bb:cc:dd:ee:ff'),
        ], 0), true);

        self::assertTrue($result->dryRun);
        self::assertSame(0, $result->ipAddressesToUpdate);
        self::assertSame(1, $result->plansToUpdate);
        self::assertSame(1, $result->macAddressesFound);
        self::assertSame(0, $result->macAddressesToComplete);
        self::assertSame(1, $result->macAddressesToUpdate);
        self::assertSame($oldPlan, $customerPlan->getPlan());
        self::assertTrue($customerPlan->isActive());
        self::assertSame('11:22:33:44:55:66', $customerPlan->getMacAddress());
    }

    private function createImporter(
        CustomerPlanRepository $customerPlanRepository,
        EntityManagerInterface $entityManager,
        array $existingPlans = [],
        ?CustomerRepository $customerRepository = null
    ): MikrotikCustomerImporter {
        if ($customerRepository === null) {
            $customerRepository = $this->createMock(CustomerRepository::class);
            $customerRepository
                ->method('existsByCustomerCode')
                ->willReturn(false);
        }

        return new MikrotikCustomerImporter(
            $customerPlanRepository,
            new CustomerCodeGenerator($customerRepository),
            new MikrotikPlanResolver($this->createPlanRepository($existingPlans), $entityManager),
            $entityManager
        );
    }

    private function createCustomerPlanRepository(
        ?CustomerPlan $planByIp = null,
        ?CustomerPlan $planByMac = null
    ): CustomerPlanRepository {
        $customerPlanRepository = $this->createMock(CustomerPlanRepository::class);
        $customerPlanRepository
            ->method('findOneByServiceIp')
            ->willReturnCallback(
                static fn (string $serviceIp): ?CustomerPlan => $planByIp?->getServiceIp() === $serviceIp ? $planByIp : null
            );
        $customerPlanRepository
            ->method('findOneByMacAddress')
            ->willReturnCallback(
                static fn (string $macAddress): ?CustomerPlan => $planByMac?->getMacAddress() === $macAddress ? $planByMac : null
            );

        return $customerPlanRepository;
    }

    /**
     * @param Plan[] $existingPlans
     */
    private function createPlanRepository(array $existingPlans = []): PlanRepository
    {
        $planRepository = $this->createMock(PlanRepository::class);
        $planRepository
            ->method('findEquivalentMikrotikPlan')
            ->willReturnCallback(static function (string $mikrotikRateKey) use ($existingPlans): ?Plan {
                foreach ($existingPlans as $plan) {
                    if ($plan->getMikrotikRateKey() === $mikrotikRateKey
                        || ($plan->getMikrotikRateKey() === null && $plan->getName() === $mikrotikRateKey)
                    ) {
                        return $plan;
                    }
                }

                return null;
            });

        return $planRepository;
    }

    /**
     * @param Customer[] $persistedCustomers
     * @param CustomerPlan[] $persistedCustomerPlans
     */
    private function createTransactionalEntityManager(
        array &$persistedCustomers = [],
        array &$persistedCustomerPlans = []
    ): EntityManagerInterface {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persistedCustomers, &$persistedCustomerPlans): void {
                if ($entity instanceof Customer) {
                    $persistedCustomers[] = $entity;
                }

                if ($entity instanceof CustomerPlan) {
                    $persistedCustomerPlans[] = $entity;
                }
            });
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $work): void {
                $work();
            });

        return $entityManager;
    }

    private function createCustomerPlanWithCustomer(
        Plan $plan,
        string $serviceIp,
        ?string $macAddress = null
    ): CustomerPlan {
        $customer = new Customer();
        $customerPlan = new CustomerPlan();
        $customerPlan->setPlan($plan);
        $customerPlan->setServiceIp($serviceIp);
        $customerPlan->setMacAddress($macAddress);
        $customer->addCustomerPlan($customerPlan);

        return $customerPlan;
    }

    private function createImportedPlan(string $mikrotikRateKey): Plan
    {
        return (new Plan())
            ->setName($mikrotikRateKey)
            ->setMikrotikRateKey($mikrotikRateKey)
            ->setMonthlyPrice('0.00')
            ->setIsActive(true);
    }

    private function queue(
        string $serviceIp,
        bool $disabled = false,
        string $planKey = '100/50',
        ?string $macAddress = null
    ): MikrotikQueue {
        [$downloadRate, $uploadRate] = explode('/', $planKey, 2);

        return new MikrotikQueue($serviceIp, $disabled, $downloadRate, $uploadRate, $planKey, $macAddress);
    }
}
