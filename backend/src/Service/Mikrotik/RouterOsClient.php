<?php

namespace App\Service\Mikrotik;

use RouterOS\Client;
use RouterOS\Query;

class RouterOsClient
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchSimpleQueues(): array
    {
        $client = new Client([
            'host' => $this->requiredEnv('MIKROTIK_HOST'),
            'port' => (int) $this->env('MIKROTIK_PORT', '8728'),
            'user' => $this->requiredEnv('MIKROTIK_USER'),
            'pass' => $this->requiredEnv('MIKROTIK_PASSWORD'),
            'timeout' => (int) $this->env('MIKROTIK_TIMEOUT', '10'),
            'socket_timeout' => (int) $this->env('MIKROTIK_SOCKET_TIMEOUT', '30'),
            'attempts' => (int) $this->env('MIKROTIK_ATTEMPTS', '3'),
            'delay' => (int) $this->env('MIKROTIK_RETRY_DELAY', '1'),
        ]);

        $response = $client->query(new Query('/queue/simple/print'))->read();

        if (!is_array($response)) {
            throw new \RuntimeException('RouterOS returned an unexpected response for simple queues.');
        }

        return $response;
    }

    private function requiredEnv(string $name): string
    {
        $value = $this->env($name);

        if ($value === null || trim($value) === '') {
            throw new \RuntimeException(sprintf('Missing required environment variable %s.', $name));
        }

        return $value;
    }

    private function env(string $name, ?string $default = null): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        if ($value === false || $value === null) {
            return $default;
        }

        return (string) $value;
    }
}
