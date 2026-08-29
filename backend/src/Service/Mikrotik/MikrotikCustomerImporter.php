<?php

namespace App\Service\Mikrotik;

use App\Entity\Customer;
use App\Entity\CustomerPlan;
use App\Entity\Plan;
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
        $ipAddressesToUpdate = 0;
        $plansToUpdate = 0;
        $macAddressesFound = 0;
        $macAddressesToComplete = 0;
        $macAddressesToUpdate = 0;
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
            &$ipAddressesToUpdate,
            &$plansToUpdate,
            &$macAddressesFound,
            &$macAddressesToComplete,
            &$macAddressesToUpdate,
            &$seenServiceIps,
            &$operationCount
        ): void {
            foreach ($queueReadResult->queues as $queue) {
                if ($queue->macAddress !== null) {
                    $macAddressesFound++;
                }

                if (isset($seenServiceIps[$queue->serviceIp])) {
                    $existing++;
                    continue;
                }

                $seenServiceIps[$queue->serviceIp] = true;
                $customerPlanByIp = $this->customerPlanRepository->findOneByServiceIp($queue->serviceIp);
                $customerPlanByMac = $queue->macAddress !== null
                    ? $this->customerPlanRepository->findOneByMacAddress($queue->macAddress)
                    : null;

                if ($customerPlanByIp instanceof CustomerPlan
                    && $customerPlanByMac instanceof CustomerPlan
                    && $customerPlanByIp !== $customerPlanByMac
                ) {
                    $ambiguous++;
                    continue;
                }

                $customerPlan = $customerPlanByIp ?? $customerPlanByMac;

                if ($customerPlan instanceof CustomerPlan) {
                    $existing++;

                    $customer = $customerPlan->getCustomer();
                    if (!$customer instanceof Customer) {
                        $ambiguous++;
                        continue;
                    }

                    $plan = $this->planResolver->resolve($queue->planKey, $dryRun)->plan;

                    if ($this->shouldUpdateServiceIp($customerPlan, $queue)) {
                        $ipAddressesToUpdate++;
                    }

                    if ($this->shouldUpdatePlan($customerPlan, $plan)) {
                        $plansToUpdate++;
                    }

                    if ($queue->macAddress !== null && $customerPlan->getMacAddress() === null) {
                        $macAddressesToComplete++;
                    } elseif ($queue->macAddress !== null && $customerPlan->getMacAddress() !== $queue->macAddress) {
                        $macAddressesToUpdate++;
                    }

                    $this->syncCustomerPlan($customerPlan, $plan, $queue, $dryRun);
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

                $this->addCustomerPlan($customer, $plan, $queue);

                $this->entityManager->persist($customer);
                $operationCount++;

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
            $ipAddressesToUpdate,
            $plansToUpdate,
            $macAddressesFound,
            $macAddressesToComplete,
            $macAddressesToUpdate,
            $dryRun
        );
    }

    private function syncCustomerPlan(CustomerPlan $customerPlan, Plan $plan, MikrotikQueue $queue, bool $dryRun): void
    {
        if ($dryRun) {
            return;
        }

        if ($this->shouldUpdateServiceIp($customerPlan, $queue)) {
            $customerPlan->setServiceIp($queue->serviceIp);
        }

        if ($this->shouldUpdatePlan($customerPlan, $plan)) {
            $customerPlan->setPlan($plan);
        }

        if ($customerPlan->isActive() !== $queue->isActive()) {
            $customerPlan->setIsActive($queue->isActive());
        }

        if ($queue->macAddress !== null && $customerPlan->getMacAddress() !== $queue->macAddress) {
            $customerPlan->setMacAddress($queue->macAddress);
        }
    }

    private function shouldUpdateServiceIp(CustomerPlan $customerPlan, MikrotikQueue $queue): bool
    {
        return $customerPlan->getServiceIp() !== $queue->serviceIp;
    }

    private function shouldUpdatePlan(CustomerPlan $customerPlan, Plan $plan): bool
    {
        return $customerPlan->getPlan() !== $plan;
    }

    private function addCustomerPlan(Customer $customer, Plan $plan, MikrotikQueue $queue): CustomerPlan
    {
        $customerPlan = new CustomerPlan();
        $customerPlan->setPlan($plan);
        $customerPlan->setIsActive($queue->isActive());
        $customerPlan->setServiceIp($queue->serviceIp);
        $customerPlan->setMacAddress($queue->macAddress);
        $customer->addCustomerPlan($customerPlan);

        $this->entityManager->persist($customerPlan);

        return $customerPlan;
    }
}
