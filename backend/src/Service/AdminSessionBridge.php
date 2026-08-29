<?php

namespace App\Service;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminSessionBridge
{
    private const MAIN_FIREWALL = 'main';
    private const MAIN_AUTHENTICATOR = 'form_login';

    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function createMainFirewallSession(UserInterface $user): void
    {
        $this->security->login(
            $user,
            self::MAIN_AUTHENTICATOR,
            self::MAIN_FIREWALL
        );
    }

    public function invalidateSession(): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request?->hasSession()) {
            $request->getSession()->invalidate();
        }

        $this->tokenStorage->setToken(null);
    }
}
