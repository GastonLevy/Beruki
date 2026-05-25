<?php

namespace App\Controller\Admin;

use App\Entity\CashCut;
use App\Entity\User;
use App\Repository\CustomerPaymentRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $user = $userRepository->find($userId);

        if (!$user instanceof User) {
            $this->addFlash('danger', 'Usuario no encontrado.');

            return $this->redirectToRoute('admin_cash_cuts_pending');
        }

        $payments = $customerPaymentRepository->findPendingPaymentsByUser($userId);

        if (count($payments) === 0) {
            $this->addFlash('warning', 'No hay pagos pendientes para este usuario.');

            return $this->redirectToRoute('admin_cash_cuts_pending');
        }

        $totalAmount = 0;

        foreach ($payments as $payment) {
            $totalAmount += (float) $payment->getAmount();
        }

        $totalAmount = number_format($totalAmount, 2, '.', '');

        $cashCut = new CashCut();
        $cashCut->setUser($user);
        $cashCut->setTotalAmount($totalAmount);
        $cashCut->setPaymentsCount(count($payments));
        $cashCut->setClosedAt(new \DateTimeImmutable());

        foreach ($payments as $payment) {
            $payment->setCashCut($cashCut);
        }

        $entityManager->persist($cashCut);
        $entityManager->flush();

        $this->addFlash('success', 'Corte de caja realizado correctamente.');

        return $this->redirectToRoute('admin_cash_cuts_pending');
    }
}