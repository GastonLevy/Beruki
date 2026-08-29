<?php

namespace App\Tests\Controller\Api;

use App\Controller\Api\AdminSessionBridgeController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AdminSessionBridgeControllerTest extends TestCase
{
    public function testSessionBridgeEndpointRequiresAdminRole(): void
    {
        $method = new \ReflectionMethod(AdminSessionBridgeController::class, 'create');
        $attributes = $method->getAttributes(IsGranted::class);

        self::assertCount(1, $attributes);
        self::assertSame('ROLE_ADMIN', $attributes[0]->getArguments()[0]);
    }

    public function testLogoutSessionEndpointRequiresAuthenticatedUser(): void
    {
        $method = new \ReflectionMethod(AdminSessionBridgeController::class, 'logout');
        $attributes = $method->getAttributes(IsGranted::class);

        self::assertCount(1, $attributes);
        self::assertSame('ROLE_USER', $attributes[0]->getArguments()[0]);
    }

    public function testControllerDoesNotExposeTokensInResponses(): void
    {
        $source = file_get_contents((new \ReflectionClass(AdminSessionBridgeController::class))->getFileName());

        self::assertIsString($source);
        self::assertStringNotContainsString('token', strtolower($source));
    }
}
