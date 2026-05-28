<?php

namespace App\Entity;

use App\Repository\PlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlanRepository::class)]
class Plan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $monthlyPrice = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, CustomerPlan>
     */
    #[ORM\OneToMany(targetEntity: CustomerPlan::class, mappedBy: 'plan', orphanRemoval: true)]
    private Collection $customerPlans;

    public function __construct()
    {
        $this->customerPlans = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getMonthlyPrice(): ?string
    {
        return $this->monthlyPrice;
    }

    public function setMonthlyPrice(string $monthlyPrice): static
    {
        $this->monthlyPrice = $monthlyPrice;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, CustomerPlan>
     */
    public function getCustomerPlans(): Collection
    {
        return $this->customerPlans;
    }

    public function addCustomerPlan(CustomerPlan $customerPlan): static
    {
        if (!$this->customerPlans->contains($customerPlan)) {
            $this->customerPlans->add($customerPlan);
            $customerPlan->setPlan($this);
        }

        return $this;
    }

    public function removeCustomerPlan(CustomerPlan $customerPlan): static
    {
        if ($this->customerPlans->removeElement($customerPlan)) {
            if ($customerPlan->getPlan() === $this) {
                $customerPlan->setPlan(null);
            }
        }

        return $this;
    }
}