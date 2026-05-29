<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\CashCutRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UserCashCutController extends AbstractController
{
    #[Route('/admin/users/{userId}/cash-cuts', name: 'admin_user_cash_cuts', methods: ['GET'])]
    public function index(
        int $userId,
        UserRepository $userRepository,
        CashCutRepository $cashCutRepository
    ): Response {
        $user = $userRepository->find($userId);

        if (!$user instanceof User) {
            throw $this->createNotFoundException('Usuario no encontrado.');
        }

        $cashCuts = $cashCutRepository->findBy(
            ['user' => $user],
            ['closedAt' => 'DESC']
        );

        return $this->render('admin/user/cash_cuts.html.twig', [
            'user' => $user,
            'cashCuts' => $cashCuts,
        ]);
    }
}