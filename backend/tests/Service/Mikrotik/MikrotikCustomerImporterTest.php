<?php

namespace App\Tests\Service\Mikrotik;

use App\Entity\Customer;
use App\Entity\CustomerConnection;
use App\Entity\CustomerPlan;
use App\Entity\Plan;
use App\Repository\CustomerConnectionRepository;
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
    public function testDuplicateServiceIpIsSkipped(): void
    {
        $connectionRepository = $this->createMock(CustomerConnectionRepository::class);
        $connectionRepository
            ->method('findOneByServiceIp')
            ->with('10.10.9.67')
            ->willReturn($this->createConnectionWithCustomer());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $result = $this->createImporter($connectionRepository, $entityManager)
            ->import(new MikrotikQueueReadResult(1, [$this->queue('10.10.9.67')], 0), true);

        self::assertSame(0, $result->created);
        self::assertSame(1, $result->existing);
    }

    public function testDryRunDoesNotPersist(): void
    {
        $connectionRepository = $this->createEmptyConnectionRepository();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');
        $entityManager->expects(self::never())->method('wrapInTransaction');

        $result = $this->createImporter($connectionRepository, $entityManager)
            ->import(new MikrotikQueueReadResult(1, [$this->queue('10.10.9.67')], 0), true);

        self::assertTrue($result->dryRun);
        self::assertSame(1, $result->created);
        self::assertSame(0, $result->existing);
        self::assertSame(1, $result->newPlans);
        self::assertSame(1, $result->customerPlansToCreate);
    }

    public function testCreatesActiveAndDisabledConnectionsWithNullCustomerNameAndCustomerPlans(): void
    {
        $persistedCustomers = [];
        $persistedConnections = [];
        $persistedPlans = [];
        $persistedCustomerPlans = [];
        $customerRepository = $this->createMock(CustomerRepository::class);
        $customerRepository
            ->expects(self::atLeastOnce())
            ->method('existsByCustomerCode')
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persistedCustomers, &$persistedConnections, &$persistedPlans, &$persistedCustomerPlans): void {
                if ($entity instanceof Customer) {
                    $persistedCustomers[] = $entity;
                }

                if ($entity instanceof CustomerConnection) {
                    $persistedConnections[] = $entity;
                }

                if ($entity instanceof Plan) {
                    $persistedPlans[] = $entity;
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

        $result = $this->createImporter($this->createEmptyConnectionRepository(), $entityManager, $customerRepository)
            ->import(new MikrotikQueueReadResult(2, [
                $this->queue('10.10.9.67', false, '100/50'),
                $this->queue('10.10.9.68', true, '100/50'),
            ], 0));

        self::assertSame(2, $result->created);
        self::assertSame(1, $result->newPlans);
        self::assertSame(2, $result->customerPlansToCreate);
        self::assertCount(2, $persistedCustomers);
        self::assertCount(2, $persistedConnections);
        self::assertCount(1, $persistedPlans);
        self::assertCount(2, $persistedCustomerPlans);
        self::assertNull($persistedCustomers[0]->getFullName());
        self::assertMatchesRegularExpression('/^\d[a-z]\d[a-z]\d[a-z]\d[a-z]$/', $persistedCustomers[0]->getCustomerCode());
        self::assertTrue($persistedConnections[0]->isActive());
        self::assertFalse($persistedConnections[1]->isActive());
        self::assertSame($persistedCustomers[0], $persistedConnections[0]->getCustomer());
        self::assertSame('100/50', $persistedPlans[0]->getName());
        self::assertSame('100/50', $persistedPlans[0]->getMikrotikRateKey());
        self::assertSame($persistedPlans[0], $persistedCustomerPlans[0]->getPlan());
        self::assertSame($persistedCustomers[0], $persistedCustomerPlans[0]->getCustomer());
    }

    public function testExistingPlanIsReusedByTechnicalKeyEvenWhenVisibleNameChanged(): void
    {
        $plan = (new Plan())
            ->setName('Fibra Hogar 100')
            ->setMikrotikRateKey('100/50')
            ->setMonthlyPrice('123.45')
            ->setIsActive(true);

        $persistedPlans = [];
        $entityManager = $this->createEntityManagerCapturingPersistedPlans($persistedPlans);

        $result = $this->createImporter(
            $this->createEmptyConnectionRepository(),
            $entityManager,
            null,
            [$plan]
        )->import(new MikrotikQueueReadResult(1, [$this->queue('10.10.9.67')], 0));

        self::assertSame(0, $result->newPlans);
        self::assertSame(1, $result->existingPlans);
        self::assertSame([], $persistedPlans);
    }

    public function testExistingConnectionGetsMissingCustomerPlanWithoutDuplicateCustomer(): void
    {
        $persistedCustomers = [];
        $persistedCustomerPlans = [];
        $connectionRepository = $this->createMock(CustomerConnectionRepository::class);
        $connectionRepository
            ->method('findOneByServiceIp')
            ->willReturn($this->createConnectionWithCustomer());

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

        $result = $this->createImporter($connectionRepository, $entityManager)
            ->import(new MikrotikQueueReadResult(1, [$this->queue('10.10.9.67')], 0));

        self::assertSame(0, $result->created);
        self::assertSame(1, $result->existing);
        self::assertSame(1, $result->customerPlansToCreate);
        self::assertCount(0, $persistedCustomers);
        self::assertCount(1, $persistedCustomerPlans);
    }

    public function testDoesNotDuplicateExistingActiveCustomerPlan(): void
    {
        $customer = new Customer();
        $connection = new CustomerConnection();
        $connection->setCustomer($customer);

        $existingCustomerPlan = new CustomerPlan();
        $existingPlan = (new Plan())
            ->setName('Fibra Hogar 100')
            ->setMikrotikRateKey('100/50')
            ->setMonthlyPrice('0.00')
            ->setIsActive(true);

        $connectionRepository = $this->createMock(CustomerConnectionRepository::class);
        $connectionRepository
            ->method('findOneByServiceIp')
            ->willReturn($connection);

        $customerPlanRepository = $this->createMock(CustomerPlanRepository::class);
        $customerPlanRepository
            ->method('findActiveOneByCustomerAndMikrotikRateKey')
            ->willReturn($existingCustomerPlan);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $work): void {
                $work();
            });

        $result = $this->createImporter(
            $connectionRepository,
            $entityManager,
            null,
            [$existingPlan],
            $customerPlanRepository
        )->import(new MikrotikQueueReadResult(1, [$this->queue('10.10.9.67')], 0));

        self::assertSame(0, $result->customerPlansToCreate);
    }

    public function testSpeedChangeDeactivatesPreviousImportedPlanAndCreatesNewCustomerPlan(): void
    {
        $customer = new Customer();
        $connection = new CustomerConnection();
        $connection->setCustomer($customer);
        $oldPlan = (new Plan())
            ->setName('100/50')
            ->setMikrotikRateKey('100/50')
            ->setMonthlyPrice('0.00')
            ->setIsActive(true);
        $oldCustomerPlan = (new CustomerPlan())->setPlan($oldPlan);

        $connectionRepository = $this->createMock(CustomerConnectionRepository::class);
        $connectionRepository
            ->method('findOneByServiceIp')
            ->willReturn($connection);

        $customerPlanRepository = $this->createMock(CustomerPlanRepository::class);
        $customerPlanRepository
            ->method('findActiveOneByCustomerAndMikrotikRateKey')
            ->willReturn(null);
        $customerPlanRepository
            ->method('findActiveImportedByCustomer')
            ->willReturn([$oldCustomerPlan]);

        $persistedCustomerPlans = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persistedCustomerPlans): void {
                if ($entity instanceof CustomerPlan) {
                    $persistedCustomerPlans[] = $entity;
                }
            });
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $work): void {
                $work();
            });

        $result = $this->createImporter(
            $connectionRepository,
            $entityManager,
            null,
            [],
            $customerPlanRepository
        )->import(new MikrotikQueueReadResult(1, [$this->queue('10.10.9.67', false, '200/100')], 0));

        self::assertSame(1, $result->customerPlansToCreate);
        self::assertFalse($oldCustomerPlan->isActive());
        self::assertCount(1, $persistedCustomerPlans);
    }

    private function createImporter(
        CustomerConnectionRepository $connectionRepository,
        EntityManagerInterface $entityManager,
        ?CustomerRepository $customerRepository = null,
        array $existingPlans = [],
        ?CustomerPlanRepository $customerPlanRepository = null
    ): MikrotikCustomerImporter {
        if ($customerRepository === null) {
            $customerRepository = $this->createMock(CustomerRepository::class);
            $customerRepository
                ->method('existsByCustomerCode')
                ->willReturn(false);
        }

        $planRepository = $this->createPlanRepository($existingPlans);
        $customerPlanRepository ??= $this->createEmptyCustomerPlanRepository();

        return new MikrotikCustomerImporter(
            $connectionRepository,
            $customerPlanRepository,
            new CustomerCodeGenerator($customerRepository),
            new MikrotikPlanResolver($planRepository, $entityManager),
            $entityManager
        );
    }

    private function createEmptyConnectionRepository(): CustomerConnectionRepository
    {
        $connectionRepository = $this->createMock(CustomerConnectionRepository::class);
        $connectionRepository
            ->method('findOneByServiceIp')
            ->willReturn(null);

        return $connectionRepository;
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

    private function createEmptyCustomerPlanRepository(): CustomerPlanRepository
    {
        $customerPlanRepository = $this->createMock(CustomerPlanRepository::class);
        $customerPlanRepository
            ->method('findActiveOneByCustomerAndMikrotikRateKey')
            ->willReturn(null);
        $customerPlanRepository
            ->method('findActiveImportedByCustomer')
            ->willReturn([]);

        return $customerPlanRepository;
    }

    /**
     * @param Plan[] $persistedPlans
     */
    private function createEntityManagerCapturingPersistedPlans(array &$persistedPlans): EntityManagerInterface
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->method('persist')
            ->willReturnCallback(function (object $entity) use (&$persistedPlans): void {
                if ($entity instanceof Plan) {
                    $persistedPlans[] = $entity;
                }
            });
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(static function (callable $work): void {
                $work();
            });

        return $entityManager;
    }

    private function createConnectionWithCustomer(): CustomerConnection
    {
        $customer = new Customer();
        $connection = new CustomerConnection();
        $connection->setCustomer($customer);

        return $connection;
    }

    private function queue(string $serviceIp, bool $disabled = false, string $planKey = '100/50'): MikrotikQueue
    {
        [$downloadRate, $uploadRate] = explode('/', $planKey, 2);

        return new MikrotikQueue($serviceIp, $disabled, $downloadRate, $uploadRate, $planKey);
    }
}
