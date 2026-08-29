<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    private const CUSTOMER_CODE_PATTERN = '/^\d[a-z]\d[a-z]\d[a-z]\d[a-z]$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 8, unique: true)]
    private ?string $customerCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $subscriberNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    private ?bool $monthlyDebt = null;

    #[ORM\Column]
    private bool $isArchived = false;

    /**
     * @var Collection<int, CustomerPlan>
     */
    #[ORM\OneToMany(
        targetEntity: CustomerPlan::class,
        mappedBy: 'customer',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $customerPlans;

    /**
     * @var Collection<int, CustomerPayment>
     */
    #[ORM\OneToMany(
        targetEntity: CustomerPayment::class,
        mappedBy: 'customer'
    )]
    private Collection $customerPayments;

    public function __construct()
    {
        $this->customerPlans = new ArrayCollection();
        $this->customerPayments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->fullName ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomerCode(): ?string
    {
        return $this->customerCode;
    }

    public function assignCustomerCode(string $customerCode): static
    {
        if (!preg_match(self::CUSTOMER_CODE_PATTERN, $customerCode)) {
            throw new \InvalidArgumentException('Customer code must match the expected public format.');
        }

        if ($this->customerCode !== null && $this->customerCode !== $customerCode) {
            throw new \LogicException('Customer code cannot be changed after it is assigned.');
        }

        $this->customerCode = $customerCode;

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getSubscriberNumber(): ?string
    {
        return $this->subscriberNumber;
    }

    public function setSubscriberNumber(?string $subscriberNumber): static
    {
        $this->subscriberNumber = $subscriberNumber;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isMonthlyDebt(): ?bool
    {
        return $this->monthlyDebt;
    }

    public function setMonthlyDebt(?bool $monthlyDebt): static
    {
        $this->monthlyDebt = $monthlyDebt;

        return $this;
    }

    public function isArchived(): bool
    {
        return $this->isArchived;
    }

    public function setIsArchived(bool $isArchived): static
    {
        $this->isArchived = $isArchived;

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
            $customerPlan->setCustomer($this);
        }

        return $this;
    }

    public function removeCustomerPlan(CustomerPlan $customerPlan): static
    {
        if ($this->customerPlans->removeElement($customerPlan)) {
            if ($customerPlan->getCustomer() === $this) {
                $customerPlan->setCustomer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomerPayment>
     */
    public function getCustomerPayments(): Collection
    {
        return $this->customerPayments;
    }

    public function hasRelations(): bool
    {
        return !$this->customerPlans->isEmpty()
            || !$this->customerPayments->isEmpty();
    }

    public function getMonthlyAmount(): string
    {
        $total = 0.0;

        foreach ($this->customerPlans as $customerPlan) {
            if (!$customerPlan->isActive()) {
                continue;
            }

            $plan = $customerPlan->getPlan();

            if ($plan === null) {
                continue;
            }

            $total += (float) $plan->getMonthlyPrice();
        }

        return number_format($total, 2, '.', '');
    }
}
