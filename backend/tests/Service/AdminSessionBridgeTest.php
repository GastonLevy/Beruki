<?php

namespace App\Tests\Service;

use App\Service\AdminSessionBridge;
use PHPUnit\Framework\TestCase;

class AdminSessionBridgeTest extends TestCase
{
    public function testUsesSymfonySecurityLoginForMainFirewall(): void
    {
        $method = new \ReflectionMethod(AdminSessionBridge::class, 'createMainFirewallSession');
        $file = $method->getFileName();

        self::assertIsString($file);

        $source = file_get_contents($file);

        self::assertIsString($source);
        self::assertStringContainsString('$this->security->login', $source);
        self::assertStringContainsString("'main'", $source);
        self::assertStringContainsString("'form_login'", $source);
    }
}
