<?php

namespace App\Http\Controllers;

use App\Services\CustomerSalesInvoiceAgingReportBuilder;
use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class CashFlowDashboardController extends Controller
{
    public function index(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ): View {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        $expectedInflows = round((float) $customerAging['summary']['remaining_total'], 2);
        $expectedOutflows = round((float) $supplierAging['summary']['remaining_total'], 2);
        $netExpectedCash = round($expectedInflows - $expectedOutflows, 2);

        return view('reports.cash-flow-dashboard', [
            'reportDate' => now()->startOfDay(),
            'inflowSummary' => [
                'customers_count' => $customerAging['summary']['customers_count'],
                'open_invoice_count' => $customerAging['summary']['invoice_count'],
                'expected_inflows' => $expectedInflows,
                'overdue_inflows' => round((float) $customerAging['summary']['overdue_total'], 2),
            ],
            'outflowSummary' => [
                'suppliers_count' => $supplierAging['summary']['suppliers_count'],
                'open_invoice_count' => $supplierAging['summary']['invoice_count'],
                'expected_outflows' => $expectedOutflows,
                'overdue_outflows' => round((float) $supplierAging['summary']['overdue_total'], 2),
            ],
            'netCashSummary' => [
                'net_expected_cash' => $netExpectedCash,
                'position_label' => $netExpectedCash >= 0
                    ? 'صافي تدفق نقدي متوقع لصالح الشركة'
                    : 'صافي التزامات نقدية متوقعة على الشركة',
            ],
            'bucketCashFlow' => $this->bucketCashFlow($customerAging['rows'], $supplierAging['rows']),
        ]);
    }

    private function bucketCashFlow(Collection $customerRows, Collection $supplierRows): array
    {
        $buckets = [
            'not_due_total' => 'غير مستحقة بعد',
            'overdue_1_30_total' => 'متأخرة 1 إلى 30',
            'overdue_31_60_total' => 'متأخرة 31 إلى 60',
            'overdue_61_90_total' => 'متأخرة 61 إلى 90',
            'overdue_more_than_90_total' => 'أكثر من 90',
            'without_due_date_total' => 'بدون تاريخ استحقاق',
        ];

        return collect($buckets)
            ->map(function (string $label, string $key) use ($customerRows, $supplierRows): array {
                $expectedInflows = round((float) $customerRows->sum(fn ($row) => (float) $row[$key]), 2);
                $expectedOutflows = round((float) $supplierRows->sum(fn ($row) => (float) $row[$key]), 2);

                return [
                    'key' => $key,
                    'label' => $label,
                    'expected_inflows' => $expectedInflows,
                    'expected_outflows' => $expectedOutflows,
                    'net_cash_flow' => round($expectedInflows - $expectedOutflows, 2),
                ];
            })
            ->values()
            ->all();
    }
}
