<?php

namespace App\Service\Mikrotik;

use App\Entity\Customer;
use App\Entity\CustomerConnection;
use App\Entity\CustomerPlan;
use App\Entity\Plan;
use App\Repository\CustomerConnectionRepository;
use App\Repository\CustomerPlanRepository;
use App\Service\CustomerCodeGenerator;
use App\Service\Mikrotik\Dto\MikrotikCustomerImportResult;
use App\Service\Mikrotik\Dto\MikrotikQueue;
use App\Service\Mikrotik\Dto\MikrotikQueueReadResult;
use Doctrine\ORM\EntityManagerInterface;

class MikrotikCustomerImporter
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly CustomerConnectionRepository $customerConnectionRepository,
        private readonly CustomerPlanRepository $customerPlanRepository,
        private readonly CustomerCodeGenerator $customerCodeGenerator,
        private readonly MikrotikPlanResolver $planResolver,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function import(MikrotikQueueReadResult $queueReadResult, bool $dryRun = false): MikrotikCustomerImportResult
    {
        $created = 0;
        $existing = 0;
        $ambiguous = 0;
        $customerPlansToCreate = 0;
        $seenServiceIps = [];
        $operationCount = 0;

        $this->planResolver->reset();

        $work = function () use (
            $queueReadResult,
            $dryRun,
            &$created,
            &$existing,
            &$ambiguous,
            &$customerPlansToCreate,
            &$seenServiceIps,
            &$operationCount
        ): void {
            foreach ($queueReadResult->queues as $queue) {
                if (isset($seenServiceIps[$queue->serviceIp])) {
                    $existing++;
                    continue;
                }

                $seenServiceIps[$queue->serviceIp] = true;
                $connection = $this->customerConnectionRepository->findOneByServiceIp($queue->serviceIp);

                if ($connection instanceof CustomerConnection) {
                    $existing++;

                    $customer = $connection->getCustomer();
                    if (!$customer instanceof Customer) {
                        $ambiguous++;
                        continue;
                    }

                    $customerPlansToCreate += $this->ensureCustomerPlan($customer, $queue, $dryRun);
                    continue;
                }

                $created++;
                $customerPlansToCreate++;
                $plan = $this->planResolver->resolve($queue->planKey, $dryRun)->plan;

                if ($dryRun) {
                    continue;
                }

                $customer = new Customer();
                $customer->assignCustomerCode($this->customerCodeGenerator->generateUnique());
                $customer->setFullName(null);
                $customer->setSubscriberNumber(null);
                $customer->setEmail(null);
                $customer->setMonthlyDebt(false);
                $customer->setIsArchived(false);

                $connection = new CustomerConnection();
                $connection->setServiceIp($queue->serviceIp);
                $connection->setMacAddress(null);
                $connection->setIsActive($queue->isActive());

                $customer->addCustomerConnection($connection);
                $this->addCustomerPlan($customer, $plan);

                $this->entityManager->persist($customer);
                $this->entityManager->persist($connection);
                $operationCount += 2;

                if ($operationCount >= self::BATCH_SIZE) {
                    $this->entityManager->flush();
                    $operationCount = 0;
                }
            }

            if (!$dryRun) {
                $this->entityManager->flush();
            }
        };

        if ($dryRun) {
            $work();
        } else {
            $this->entityManager->wrapInTransaction($work);
        }

        return new MikrotikCustomerImportResult(
            $queueReadResult->queuesRead,
            $created,
            $existing,
            $queueReadResult->invalidQueues,
            $ambiguous,
            $this->planResolver->getPlansDiscovered(),
            $this->planResolver->getNewPlans(),
            $this->planResolver->getExistingPlans(),
            $customerPlansToCreate,
            $dryRun
        );
    }

    private function ensureCustomerPlan(Customer $customer, MikrotikQueue $queue, bool $dryRun): int
    {
        $plan = $this->planResolver->resolve($queue->planKey, $dryRun)->plan;

        if ($this->customerPlanRepository->findActiveOneByCustomerAndMikrotikRateKey($customer, $queue->planKey) instanceof CustomerPlan) {
            return 0;
        }

        if ($dryRun) {
            return 1;
        }

        foreach ($this->customerPlanRepository->findActiveImportedByCustomer($customer) as $activeImportedPlan) {
            $activeImportedPlan->setIsActive(false);
        }

        $this->addCustomerPlan($customer, $plan);

        return 1;
    }

    private function addCustomerPlan(Customer $customer, Plan $plan): CustomerPlan
    {
        $customerPlan = new CustomerPlan();
        $customerPlan->setPlan($plan);
        $customerPlan->setIsActive(true);
        $customer->addCustomerPlan($customerPlan);

        $this->entityManager->persist($customerPlan);

        return $customerPlan;
    }
}
