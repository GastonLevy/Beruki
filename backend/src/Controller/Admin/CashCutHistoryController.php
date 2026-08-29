<?php

namespace App\Controller\Admin;

use App\Entity\CashCut;
use App\Repository\CashCutRepository;
use App\Service\CashCutExcelReportBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CashCutHistoryController extends AbstractController
{
    #[Route('/admin/cash-cuts/history', name: 'admin_cash_cuts_history', methods: ['GET'])]
    public function index(
        Request $request,
        CashCutRepository $cashCutRepository
    ): Response {
        $username = $request->query->get('username');
        $dateFromValue = $request->query->get('dateFrom');
        $dateToValue = $request->query->get('dateTo');
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);

        $cashCuts = $cashCutRepository->findHistoryWithFilters(
            $username,
            $dateFrom,
            $dateTo
        );

        return $this->render('admin/cash_cut_history/index.html.twig', [
            'cashCuts' => $cashCuts,
            'filters' => [
                'username' => $username,
                'dateFrom' => $dateFromValue,
                'dateTo' => $dateToValue,
            ],
        ]);
    }

    #[Route('/admin/cash-cuts/history/export', name: 'admin_cash_cuts_history_export', methods: ['GET'])]
    public function export(
        Request $request,
        CashCutRepository $cashCutRepository,
        CashCutExcelReportBuilder $reportBuilder
    ): Response {
        [$dateFrom, $dateTo] = $this->resolveDateRange($request);
        $cashCuts = $cashCutRepository->findClosedReportCashCuts($dateFrom, $dateTo);

        return new Response(
            $reportBuilder->build($cashCuts),
            Response::HTTP_OK,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => sprintf(
                    'attachment; filename="%s"',
                    $this->reportFilename($dateFrom, $dateTo)
                ),
            ]
        );
    }

    #[Route(
        '/admin/cash-cuts/history/{cashCutId}',
        name: 'admin_cash_cuts_history_detail',
        requirements: ['cashCutId' => '\d+'],
        methods: ['GET']
    )]
    public function detail(
        int $cashCutId,
        CashCutRepository $cashCutRepository
    ): Response {
        $cashCut = $cashCutRepository->find($cashCutId);

        if (!$cashCut instanceof CashCut) {
            throw $this->createNotFoundException('Corte de caja no encontrado.');
        }

        return $this->render('admin/cash_cut_history/detail.html.twig', [
            'cashCut' => $cashCut,
            'payments' => $cashCut->getCustomerPayments(),
        ]);
    }

    /**
     * @return array{?\DateTimeImmutable, ?\DateTimeImmutable}
     */
    private function resolveDateRange(Request $request): array
    {
        $dateFromValue = $request->query->get('dateFrom');
        $dateToValue = $request->query->get('dateTo');

        $dateFrom = null;
        $dateTo = null;

        if ($dateFromValue) {
            $dateFrom = new \DateTimeImmutable($dateFromValue . ' 00:00:00');
        }

        if ($dateToValue) {
            $dateTo = new \DateTimeImmutable($dateToValue . ' 23:59:59');
        }

        return [$dateFrom, $dateTo];
    }

    private function reportFilename(?\DateTimeImmutable $dateFrom, ?\DateTimeImmutable $dateTo): string
    {
        return sprintf(
            'corte_caja_%s_%s.xlsx',
            $dateFrom?->format('Y-m-d') ?? '0000-00-00',
            $dateTo?->format('Y-m-d') ?? '9999-12-31'
        );
    }
}
