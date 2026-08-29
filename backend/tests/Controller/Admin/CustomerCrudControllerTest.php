<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\CustomerCrudController;
use App\Service\MonthlyDebtGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CustomerCrudControllerTest extends TestCase
{
    public function testMonthlyDebtGenerationActionIsAdminOnlyPostAction(): void
    {
        $method = new \ReflectionMethod(CustomerCrudController::class, 'generateMonthlyDebt');

        $adminRoutes = $method->getAttributes(AdminRoute::class);
        $isGranted = $method->getAttributes(IsGranted::class);

        self::assertCount(1, $adminRoutes);
        self::assertSame('/generate-monthly-debt', $adminRoutes[0]->getArguments()['path']);
        self::assertSame(['POST'], $adminRoutes[0]->getArguments()['options']['methods']);
        self::assertCount(1, $isGranted);
        self::assertSame('ROLE_ADMIN', $isGranted[0]->getArguments()[0]);
    }

    public function testMonthlyDebtGenerationActionDelegatesToService(): void
    {
        $parameters = (new \ReflectionMethod(CustomerCrudController::class, 'generateMonthlyDebt'))
            ->getParameters();

        self::assertCount(1, $parameters);
        self::assertSame(MonthlyDebtGenerator::class, $parameters[0]->getType()?->getName());
    }
}
