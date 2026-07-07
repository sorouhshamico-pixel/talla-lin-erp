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

        $data = $this->dashboardData($customerAging, $supplierAging);

        return view('reports.cash-flow-dashboard', $data);
    }

    public function export(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ) {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        $data = $this->dashboardData($customerAging, $supplierAging);

        $fileName = 'cash-flow-dashboard-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['لوحة التدفق النقدي المتوقع']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $data['reportDate']->format('Y-m-d')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص التدفقات الداخلة']);
            fputcsv($handle, ['عدد العملاء أصحاب الذمم', $data['inflowSummary']['customers_count']]);
            fputcsv($handle, ['فواتير العملاء المفتوحة', $data['inflowSummary']['open_invoice_count']]);
            fputcsv($handle, ['التدفقات الداخلة المتوقعة', number_format((float) $data['inflowSummary']['expected_inflows'], 2, '.', '')]);
            fputcsv($handle, ['تدفقات داخلة متأخرة', number_format((float) $data['inflowSummary']['overdue_inflows'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص التدفقات الخارجة']);
            fputcsv($handle, ['عدد الموردين أصحاب الذمم', $data['outflowSummary']['suppliers_count']]);
            fputcsv($handle, ['فواتير الموردين المفتوحة', $data['outflowSummary']['open_invoice_count']]);
            fputcsv($handle, ['التدفقات الخارجة المتوقعة', number_format((float) $data['outflowSummary']['expected_outflows'], 2, '.', '')]);
            fputcsv($handle, ['تدفقات خارجة متأخرة', number_format((float) $data['outflowSummary']['overdue_outflows'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['صافي التدفق النقدي']);
            fputcsv($handle, ['صافي التدفق النقدي المتوقع', number_format((float) $data['netCashSummary']['net_expected_cash'], 2, '.', '')]);
            fputcsv($handle, ['حالة التدفق النقدي المتوقع', $data['netCashSummary']['position_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['مخاطر التدفق النقدي']);
            fputcsv($handle, ['إجمالي التدفقات الداخلة المتأخرة', number_format((float) $data['riskSummary']['overdue_inflows'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي التدفقات الخارجة المتأخرة', number_format((float) $data['riskSummary']['overdue_outflows'], 2, '.', '')]);
            fputcsv($handle, ['صافي الضغط النقدي المتأخر', number_format((float) $data['riskSummary']['net_overdue_pressure'], 2, '.', '')]);
            fputcsv($handle, ['حالة الضغط النقدي', $data['riskSummary']['pressure_label']]);
            fputcsv($handle, ['نسبة تغطية الالتزامات المتوقعة', $data['riskSummary']['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $data['riskSummary']['cash_coverage_ratio'], 2, '.', '') . '%']);
            fputcsv($handle, ['حالة التغطية النقدية', $data['riskSummary']['coverage_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['التدفق النقدي حسب شرائح الأعمار']);
            fputcsv($handle, ['شريحة العمر', 'تدفقات داخلة متوقعة', 'تدفقات خارجة متوقعة', 'صافي التدفق النقدي']);

            foreach ($data['bucketCashFlow'] as $bucket) {
                fputcsv($handle, [
                    $bucket['label'],
                    number_format((float) $bucket['expected_inflows'], 2, '.', ''),
                    number_format((float) $bucket['expected_outflows'], 2, '.', ''),
                    number_format((float) $bucket['net_cash_flow'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function dashboardData(array $customerAging, array $supplierAging): array
    {
        $expectedInflows = round((float) $customerAging['summary']['remaining_total'], 2);
        $expectedOutflows = round((float) $supplierAging['summary']['remaining_total'], 2);
        $overdueInflows = round((float) $customerAging['summary']['overdue_total'], 2);
        $overdueOutflows = round((float) $supplierAging['summary']['overdue_total'], 2);
        $netExpectedCash = round($expectedInflows - $expectedOutflows, 2);

        return [
            'reportDate' => now()->startOfDay(),
            'inflowSummary' => [
                'customers_count' => $customerAging['summary']['customers_count'],
                'open_invoice_count' => $customerAging['summary']['invoice_count'],
                'expected_inflows' => $expectedInflows,
                'overdue_inflows' => $overdueInflows,
            ],
            'outflowSummary' => [
                'suppliers_count' => $supplierAging['summary']['suppliers_count'],
                'open_invoice_count' => $supplierAging['summary']['invoice_count'],
                'expected_outflows' => $expectedOutflows,
                'overdue_outflows' => $overdueOutflows,
            ],
            'netCashSummary' => [
                'net_expected_cash' => $netExpectedCash,
                'position_label' => $netExpectedCash >= 0
                    ? 'صافي تدفق نقدي متوقع لصالح الشركة'
                    : 'صافي التزامات نقدية متوقعة على الشركة',
            ],
            'riskSummary' => $this->riskSummary($overdueInflows, $overdueOutflows, $expectedInflows, $expectedOutflows),
            'bucketCashFlow' => $this->bucketCashFlow($customerAging['rows'], $supplierAging['rows']),
        ];
    }

    private function riskSummary(float $overdueInflows, float $overdueOutflows, float $expectedInflows, float $expectedOutflows): array
    {
        $netOverduePressure = round($overdueOutflows - $overdueInflows, 2);
        $cashCoverageRatio = $expectedOutflows > 0
            ? round(($expectedInflows / $expectedOutflows) * 100, 2)
            : null;

        return [
            'overdue_inflows' => $overdueInflows,
            'overdue_outflows' => $overdueOutflows,
            'net_overdue_pressure' => $netOverduePressure,
            'cash_coverage_ratio' => $cashCoverageRatio,
            'pressure_label' => $netOverduePressure > 0
                ? 'ضغط نقدي متأخر على الشركة'
                : 'المتأخرات الداخلة تغطي الالتزامات المتأخرة',
            'coverage_label' => $cashCoverageRatio === null
                ? 'لا توجد التزامات خارجة مفتوحة'
                : ($cashCoverageRatio >= 100 ? 'تغطية نقدية متوقعة كافية' : 'تغطية نقدية متوقعة غير كافية'),
        ];
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
