<?php

namespace App\Controller\Api;

use App\Repository\CustomerRepository;
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
        $limit = max(1, min(100, $request->query->getInt('limit', 10)));
        $offset = ($page - 1) * $limit;

        $customers = $customerRepository->findBy(
            [],
            ['id' => 'DESC'],
            $limit,
            $offset
        );

        $total = $customerRepository->count([]);

        return $this->json([
            'data' => array_map(
                fn ($customer) => $this->serializeCustomer($customer),
                $customers
            ),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => (int) ceil($total / $limit),
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