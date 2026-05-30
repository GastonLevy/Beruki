<?php

namespace App\Entity;

use App\Repository\CashCutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CashCutRepository::class)]
class CashCut
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'cashCuts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $userCommissionAmount = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amountToWithdraw = null;

    #[ORM\Column]
    private ?int $paymentsCount = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $closedAt = null;

    /**
     * @var Collection<int, CustomerPayment>
     */
    #[ORM\OneToMany(targetEntity: CustomerPayment::class, mappedBy: 'cashCut')]
    private Collection $customerPayments;

    public function __construct()
    {
        $this->customerPayments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getUserCommissionAmount(): ?string
    {
        return $this->userCommissionAmount;
    }

    public function setUserCommissionAmount(string $userCommissionAmount): static
    {
        $this->userCommissionAmount = $userCommissionAmount;

        return $this;
    }

    public function getAmountToWithdraw(): ?string
    {
        return $this->amountToWithdraw;
    }

    public function setAmountToWithdraw(string $amountToWithdraw): static
    {
        $this->amountToWithdraw = $amountToWithdraw;

        return $this;
    }

    public function getPaymentsCount(): ?int
    {
        return $this->paymentsCount;
    }

    public function setPaymentsCount(int $paymentsCount): static
    {
        $this->paymentsCount = $paymentsCount;

        return $this;
    }

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, CustomerPayment>
     */
    public function getCustomerPayments(): Collection
    {
        return $this->customerPayments;
    }

    public function addCustomerPayment(CustomerPayment $customerPayment): static
    {
        if (!$this->customerPayments->contains($customerPayment)) {
            $this->customerPayments->add($customerPayment);
            $customerPayment->setCashCut($this);
        }

        return $this;
    }

    public function removeCustomerPayment(CustomerPayment $customerPayment): static
    {
        if ($this->customerPayments->removeElement($customerPayment)) {
            if ($customerPayment->getCashCut() === $this) {
                $customerPayment->setCashCut(null);
            }
        }

        return $this;
    }
}