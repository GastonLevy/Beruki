<?php

namespace App\Controller\Admin;

use App\Entity\CashCut;
use App\Entity\User;
use App\Repository\CustomerPaymentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CashCutDashboardController extends AbstractController
{
    #[Route('/admin/cash-cuts/pending', name: 'admin_cash_cuts_pending', methods: ['GET'])]
    public function index(
        CustomerPaymentRepository $customerPaymentRepository
    ): Response {
        return $this->render('admin/cash_cut_dashboard/index.html.twig', [
            'pendingCollections' => $customerPaymentRepository->getPendingCollectionByUser(),
        ]);
    }

    #[Route('/admin/cash-cuts/{userId}/close', name: 'admin_cash_cuts_close', methods: ['POST'])]
    public function close(
        int $userId,
        UserRepository $userRepository,
        CustomerPaymentRepository $customerPaymentRepository,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator
    ): RedirectResponse {
        $user = $userRepository->find($userId);

        if (!$user instanceof User) {
            $this->addFlash('danger', 'Usuario no encontrado.');

            return $this->redirectToPendingCashCuts($adminUrlGenerator);
        }

        $payments = $customerPaymentRepository->findPendingPaymentsByUser($userId);

        if (count($payments) === 0) {
            $this->addFlash('warning', 'No hay pagos pendientes para este usuario.');

            return $this->redirectToPendingCashCuts($adminUrlGenerator);
        }

        $totalAmount = 0;

        foreach ($payments as $payment) {
            $totalAmount += (float) $payment->getAmount();
        }

        $commissionPercentage = (float) $user->getCommissionPercentage();

        $userCommissionAmount = $totalAmount * $commissionPercentage / 100;
        $amountToWithdraw = $totalAmount - $userCommissionAmount;

        $cashCut = new CashCut();
        $cashCut->setUser($user);
        $cashCut->setTotalAmount(number_format($totalAmount, 2, '.', ''));
        $cashCut->setUserCommissionAmount(number_format($userCommissionAmount, 2, '.', ''));
        $cashCut->setAmountToWithdraw(number_format($amountToWithdraw, 2, '.', ''));
        $cashCut->setPaymentsCount(count($payments));
        $cashCut->setClosedAt(new \DateTimeImmutable());

        foreach ($payments as $payment) {
            $payment->setCashCut($cashCut);
        }

        $entityManager->persist($cashCut);
        $entityManager->flush();

        $this->addFlash('success', 'Corte de caja realizado correctamente.');

        return $this->redirectToPendingCashCuts($adminUrlGenerator);
    }

    private function redirectToPendingCashCuts(AdminUrlGenerator $adminUrlGenerator): RedirectResponse
    {
        return $this->redirect(
            $adminUrlGenerator
                ->unsetAll()
                ->setRoute('admin_cash_cuts_pending')
                ->generateUrl()
        );
    }

    #[Route('/admin/cash-cuts/{userId}/detail', name: 'admin_cash_cuts_detail', methods: ['GET'])]
    public function detail(
        int $userId,
        UserRepository $userRepository,
        CustomerPaymentRepository $customerPaymentRepository
    ): Response {
        $user = $userRepository->find($userId);

        if (!$user instanceof User) {
            $this->addFlash('danger', 'Usuario no encontrado.');

            return $this->redirectToRoute('admin_cash_cuts_pending');
        }

        $payments = $customerPaymentRepository->findPendingPaymentsByUserWithCustomer($userId);

        $totalAmount = 0;

        foreach ($payments as $payment) {
            $totalAmount += (float) $payment->getAmount();
        }

        $commissionPercentage = (float) $user->getCommissionPercentage();

        $userCommissionAmount = $totalAmount * $commissionPercentage / 100;
        $amountToWithdraw = $totalAmount - $userCommissionAmount;

        return $this->render('admin/cash_cut_dashboard/detail.html.twig', [
            'user' => $user,
            'payments' => $payments,
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
            'userCommissionAmount' => number_format($userCommissionAmount, 2, '.', ''),
            'amountToWithdraw' => number_format($amountToWithdraw, 2, '.', ''),
        ]);
    }
}