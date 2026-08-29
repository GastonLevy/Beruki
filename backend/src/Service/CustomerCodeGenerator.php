<?php

namespace App\Service;

use App\Repository\CustomerRepository;

final class CustomerCodeGenerator
{
    private const DIGITS = '0123456789';
    private const LETTERS = 'abcdefghijklmnopqrstuvwxyz';

    /**
     * @var array<string, true>
     */
    private array $reservedCodes = [];

    public function __construct(
        private readonly CustomerRepository $customerRepository,
    ) {
    }

    public function generateUnique(): string
    {
        do {
            $customerCode = $this->generate();
        } while (isset($this->reservedCodes[$customerCode]) || $this->customerRepository->existsByCustomerCode($customerCode));

        $this->reservedCodes[$customerCode] = true;

        return $customerCode;
    }

    private function generate(): string
    {
        $customerCode = '';

        for ($i = 0; $i < 4; $i++) {
            $customerCode .= self::DIGITS[random_int(0, strlen(self::DIGITS) - 1)];
            $customerCode .= self::LETTERS[random_int(0, strlen(self::LETTERS) - 1)];
        }

        return $customerCode;
    }
}
