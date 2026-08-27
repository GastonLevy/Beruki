<?php

namespace App\Entity;

use App\Repository\CustomerConnectionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerConnectionRepository::class)]
class CustomerConnection
{
    private const MAC_ADDRESS_PATTERN = '/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customerConnections')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Customer $customer = null;

    #[ORM\Column(length: 45, nullable: true, unique: true)]
    private ?string $serviceIp = null;

    #[ORM\Column(length: 17, nullable: true)]
    private ?string $macAddress = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
