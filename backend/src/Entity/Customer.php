<?php

namespace App\Entity;

use App\Repository\CustomerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $fullName = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $subscriberNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $monthlyAmount = null;

    #[ORM\Column(nullable: true)]
    private ?bool $monthlyDebt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(string $fullName): static
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

    public function getMonthlyAmount(): ?string
    {
        return $this->monthlyAmount;
    }

    public function setMonthlyAmount(?string $monthlyAmount): static
    {
        $this->monthlyAmount = $monthlyAmount;

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
}
