<?php

namespace App\Service\Mikrotik;

use App\Service\Mikrotik\Dto\MikrotikQueue;
use App\Service\Mikrotik\Dto\MikrotikQueueReadResult;

class MikrotikQueueReader
{
    public function __construct(
        private readonly RouterOsClient $routerOsClient,
    ) {
    }

    public function readSimpleQueues(): MikrotikQueueReadResult
    {
        $rawQueues = $this->routerOsClient->fetchSimpleQueues();
        $macAddressesByIp = $this->readMacAddressesByIp();
        $queues = [];
        $invalidQueues = 0;

        foreach ($rawQueues as $rawQueue) {
            $serviceIp = $this->normalizeTarget($rawQueue['target'] ?? null);
            $planData = $this->normalizeMaxLimit($rawQueue['max-limit'] ?? null);

            if ($serviceIp === null || $planData === null) {
                $invalidQueues++;
                continue;
            }

            $queues[] = new MikrotikQueue(
                $serviceIp,
                $this->isDisabled($rawQueue['disabled'] ?? null),
                $planData['downloadRate'],
                $planData['uploadRate'],
                $planData['planKey'],
                $macAddressesByIp[$serviceIp] ?? null
            );
        }

        return new MikrotikQueueReadResult(
            count($rawQueues),
            $queues,
            $invalidQueues
        );
    }

    public function normalizeTarget(mixed $target): ?string
    {
        if (!is_string($target)) {
            return null;
        }

        $target = trim($target);

        if ($target === '' || str_contains($target, ',') || preg_match('/\s/', $target)) {
            return null;
        }

        if (!preg_match('/^(.+?)(?:\/(\d{1,3}))?$/', $target, $matches)) {
            return null;
        }

        $ip = $matches[1];
        $cidr = isset($matches[2]) ? (int) $matches[2] : null;

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }

        if ($cidr !== null && !$this->isHostCidr($ip, $cidr)) {
            return null;
        }

        return $ip;
    }

    /**
     * @return array{downloadRate: string, uploadRate: string, planKey: string}|null
     */
    public function normalizeMaxLimit(mixed $maxLimit): ?array
    {
        if (!is_string($maxLimit)) {
            return null;
        }

        $maxLimit = trim($maxLimit);

        if ($maxLimit === '' || substr_count($maxLimit, '/') !== 1) {
            return null;
        }

        [$upload, $download] = array_map('trim', explode('/', $maxLimit, 2));
        $uploadRate = $this->normalizeRateToMbps($upload);
        $downloadRate = $this->normalizeRateToMbps($download);

        if ($uploadRate === null || $downloadRate === null) {
            return null;
        }

        return [
            'downloadRate' => $downloadRate,
            'uploadRate' => $uploadRate,
            'planKey' => sprintf('%s/%s', $downloadRate, $uploadRate),
        ];
    }

    private function normalizeRateToMbps(string $rate): ?string
    {
        if (!preg_match('/^(\d+(?:\.\d+)?)([kKmMgG]?)$/', $rate, $matches)) {
            return null;
        }

        $value = (float) $matches[1];
        $unit = strtolower($matches[2]);

        if ($value <= 0) {
            return null;
        }

        $mbps = match ($unit) {
            'g' => $value * 1000,
            'm' => $value,
            'k' => $value / 1000,
            '' => $value / 1000000,
        };

        if ($mbps <= 0 || !is_finite($mbps)) {
            return null;
        }

        return rtrim(rtrim(sprintf('%.6F', $mbps), '0'), '.');
    }

    private function isHostCidr(string $ip, int $cidr): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $cidr === 32;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $cidr === 128;
        }

        return false;
    }

    private function isDisabled(mixed $disabled): bool
    {
        if (is_bool($disabled)) {
            return $disabled;
        }

        if (!is_string($disabled)) {
            return false;
        }

        return in_array(strtolower(trim($disabled)), ['yes', 'true', '1'], true);
    }

    public function normalizeMacAddress(mixed $macAddress): ?string
    {
        if (!is_string($macAddress)) {
            return null;
        }

        $macAddress = strtolower(str_replace('-', ':', trim($macAddress)));

        if ($macAddress === '') {
            return null;
        }

        if (!preg_match('/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/', $macAddress)) {
            return null;
        }

        return $macAddress;
    }

    /**
     * @return array<string, string>
     */
    private function readMacAddressesByIp(): array
    {
        $macAddressesByIp = [];

        foreach ($this->routerOsClient->fetchArpEntries() as $rawArpEntry) {
            $ip = $this->normalizeTarget($rawArpEntry['address'] ?? null);
            $macAddress = $this->normalizeMacAddress($rawArpEntry['mac-address'] ?? null);

            if ($ip === null || $macAddress === null) {
                continue;
            }

            $macAddressesByIp[$ip] = $macAddress;
        }

        return $macAddressesByIp;
    }
}
