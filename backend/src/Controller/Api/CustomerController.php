<?php

namespace App\Controller\Api;

use App\Entity\CustomerPayment;
use App\Entity\User;
use App\Repository\CustomerPaymentRepository;
use App\Repository\CustomerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/customers')]
final class CustomerController extends AbstractController
{
    #[Route('', name: 'api_customers_index', methods: ['GET'])]
    public function index(
        Request $request,
        CustomerRepository $customerRepository
    ): JsonResponse {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, min(100, $request->query->getInt('limit', 25)));
        $offset = ($page - 1) * $limit;
        $search = trim($request->query->get('search', ''));

        $result = $customerRepository->searchPaginated(
            $search,
            $limit,
            $offset
        );

        return $this->json([
            'data' => array_map(
                fn($customer) => $this->serializeCustomer($customer),
                $result['data']
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $result['total'],
                'pages' => (int) ceil($result['total'] / $limit),
            ],
        ]);
    }

    #[Route('/{id}', name: 'api_customers_show', methods: ['GET'])]
    public function show(
        int $id,
        CustomerRepository $customerRepository
    ): JsonResponse {
        $customer = $customerRepository->find($id);

        if (!$customer) {
            return $this->json([
                'message' => 'Customer not found',
            ], 404);
        }

        return $this->json($this->serializeCustomer($customer));
    }

    #[Route('/{id}/payments', name: 'api_customers_payments_create', methods: ['POST'])]
    public function createPayment(
        int $id,
        CustomerRepository $customerRepository,
        CustomerPaymentRepository $customerPaymentRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $customer = $customerRepository->find($id);

        if (!$customer) {
            return $this->json([
                'message' => 'Customer not found',
            ], 404);
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json([
                'message' => 'User not authenticated',
            ], 401);
        }

        if ($customerPaymentRepository->hasPaymentForCurrentMonth($customer->getId())) {
            return $this->json([
                'message' => 'Customer already paid this month',
            ], 409);
        }

        $amount = $customer->getMonthlyAmount();

        if (!$amount) {
            return $this->json([
                'message' => 'Customer monthly amount is not configured',
            ], 400);
        }

        $payment = new CustomerPayment();
        $payment->setCustomer($customer);
        $payment->setUser($user);
        $payment->setAmount((string) $amount);
        $payment->setPaidAt(new \DateTimeImmutable());

        $customer->setMonthlyDebt(false);

        $entityManager->persist($payment);
        $entityManager->flush();

        return $this->json([
            'id' => $payment->getId(),
            'customerId' => $customer->getId(),
            'userId' => $user->getId(),
            'amount' => $payment->getAmount(),
            'paidAt' => $payment->getPaidAt()?->format('Y-m-d H:i:s'),
        ], 201);
    }

    private function serializeCustomer(object $customer): array
    {
        return [
            'id' => $customer->getId(),
            'fullName' => $customer->getFullName(),
            'subscriberNumber' => $customer->getSubscriberNumber(),
            'email' => $customer->getEmail(),
            'monthlyAmount' => $customer->getMonthlyAmount(),
            'monthlyDebt' => $customer->isMonthlyDebt(),
        ];
    }
}