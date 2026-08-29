<?php

namespace App\Entity;

use App\Repository\CustomerPlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerPlanRepository::class)]
class CustomerPlan
{
    private const MAC_ADDRESS_PATTERN = '/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customerPlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(inversedBy: 'customerPlans')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Plan $plan = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(length: 45, nullable: true, unique: true)]
    private ?string $serviceIp = null;

    #[ORM\Column(length: 17, nullable: true)]
    private ?string $macAddress = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable();
        $this->isActive = true;
    }

    public function __toString(): string
    {
        $customerName = $this->customer?->getFullName() ?? 'Cliente sin nombre';
        $planName = $this->plan?->getName() ?? 'Plan sin nombre';

        return sprintf('%s - %s', $customerName, $planName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    public function setPlan(?Plan $plan): static
    {
        $this->plan = $plan;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt ?? new \DateTimeImmutable();

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(?bool $isActive): static
    {
        $this->isActive = $isActive ?? true;

        return $this;
    }

    public function getServiceIp(): ?string
    {
        return $this->serviceIp;
    }

    public function setServiceIp(?string $serviceIp): static
    {
        $serviceIp = $serviceIp !== null ? trim($serviceIp) : null;
        $this->serviceIp = $serviceIp === '' ? null : $serviceIp;

        return $this;
    }

    public function getMacAddress(): ?string
    {
        return $this->macAddress;
    }

    public function setMacAddress(?string $macAddress): static
    {
        $macAddress = $macAddress !== null ? strtolower(str_replace('-', ':', trim($macAddress))) : null;

        if ($macAddress === '') {
            $this->macAddress = null;

            return $this;
        }

        if ($macAddress !== null && !preg_match(self::MAC_ADDRESS_PATTERN, $macAddress)) {
            throw new \InvalidArgumentException('MAC address must use the format aa:bb:cc:dd:ee:ff.');
        }

        $this->macAddress = $macAddress;

        return $this;
    }
}
