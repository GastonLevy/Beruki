<?php

namespace App\Service\Mikrotik;

use App\Entity\Plan;
use App\Repository\PlanRepository;
use App\Service\Mikrotik\Dto\MikrotikPlanResolution;
use Doctrine\ORM\EntityManagerInterface;

class MikrotikPlanResolver
{
    /**
     * @var array<string, Plan>
     */
    private array $plansByRateKey = [];

    /**
     * @var array<string, true>
     */
    private array $newPlanKeys = [];

    /**
     * @var array<string, true>
     */
    private array $existingPlanKeys = [];

    public function __construct(
        private readonly PlanRepository $planRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function resolve(string $mikrotikRateKey, bool $dryRun): MikrotikPlanResolution
    {
        if (isset($this->plansByRateKey[$mikrotikRateKey])) {
            return new MikrotikPlanResolution(
                $this->plansByRateKey[$mikrotikRateKey],
                isset($this->newPlanKeys[$mikrotikRateKey])
            );
        }

        $plan = $this->planRepository->findEquivalentMikrotikPlan($mikrotikRateKey);

        if ($plan instanceof Plan) {
            $this->existingPlanKeys[$mikrotikRateKey] = true;

            if ($plan->getMikrotikRateKey() === null && !$dryRun) {
                $plan->setMikrotikRateKey($mikrotikRateKey);
            }

            $this->plansByRateKey[$mikrotikRateKey] = $plan;

            return new MikrotikPlanResolution($plan, false);
        }

        $plan = new Plan();
        $plan->setName($mikrotikRateKey);
        $plan->setMikrotikRateKey($mikrotikRateKey);
        $plan->setMonthlyPrice('0.00');
        $plan->setDescription(null);
        $plan->setIsActive(true);

        $this->newPlanKeys[$mikrotikRateKey] = true;
        $this->plansByRateKey[$mikrotikRateKey] = $plan;

        if (!$dryRun) {
            $this->entityManager->persist($plan);
        }

        return new MikrotikPlanResolution($plan, true);
    }

    public function reset(): void
    {
        $this->plansByRateKey = [];
        $this->newPlanKeys = [];
        $this->existingPlanKeys = [];
    }

    public function getPlansDiscovered(): int
    {
        return count($this->plansByRateKey);
    }

    public function getNewPlans(): int
    {
        return count($this->newPlanKeys);
    }

    public function getExistingPlans(): int
    {
        return count($this->existingPlanKeys);
    }
}
