<?php

namespace App\Controller\Api;

use App\Service\AdminSessionBridge;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/admin')]
final class AdminSessionBridgeController extends AbstractController
{
    #[Route('/session-bridge', name: 'api_admin_session_bridge_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(AdminSessionBridge $adminSessionBridge): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof UserInterface) {
            return $this->json(['message' => 'User not authenticated'], 401);
        }

        $adminSessionBridge->createMainFirewallSession($user);

        return $this->json(['status' => 'ok']);
    }

    #[Route('/logout-session', name: 'api_admin_session_bridge_logout', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function logout(AdminSessionBridge $adminSessionBridge): JsonResponse
    {
        $adminSessionBridge->invalidateSession();

        return $this->json(['status' => 'ok']);
    }
}
