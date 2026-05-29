<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Repository\CustomerPaymentRepository;
use App\Repository\CustomerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CustomerDetailController extends AbstractController
{
    #[Route('/admin/customers/{customerId}/detail', name: 'admin_customer_detail_view', methods: ['GET'])]
    public function detail(
        int $customerId,
        CustomerRepository $customerRepository,
        CustomerPaymentRepository $customerPaymentRepository
    ): Response {
        $customer = $customerRepository->find($customerId);

        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Cliente no encontrado.');
        }

        $payments = $customerPaymentRepository->findBy(
            ['customer' => $customer],
            ['paidAt' => 'DESC']
        );

        return $this->render('admin/customer/detail.html.twig', [
            'customer' => $customer,
            'payments' => $payments,
        ]);
    }
}