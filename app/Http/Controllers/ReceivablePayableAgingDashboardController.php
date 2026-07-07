<?php

namespace App\Http\Controllers;

use App\Services\CustomerSalesInvoiceAgingReportBuilder;
use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceivablePayableAgingDashboardController extends Controller
{
    public function index(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ): View {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        return view('reports.receivable-payable-aging-dashboard', $this->dashboardData($customerAging, $supplierAging, $request));
    }

    public function print(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ): View {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        return view('reports.receivable-payable-aging-dashboard-print', $this->dashboardData($customerAging, $supplierAging, $request));
    }

    public function export(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ) {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        $data = $this->dashboardData($customerAging, $supplierAging, $request);

        $fileName = 'receivable-payable-aging-dashboard-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['لوحة أعمار الذمم']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $data['reportDate']->format('Y-m-d')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص ذمم العملاء']);
            fputcsv($handle, ['عدد العملاء', $data['customerSummary']['customers_count']]);
            fputcsv($handle, ['فواتير العملاء المفتوحة', $data['customerSummary']['invoice_count']]);
            fputcsv($handle, ['إجمالي ذمم العملاء المفتوحة', number_format((float) $data['customerSummary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر على العملاء', number_format((float) $data['customerSummary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص ذمم الموردين']);
            fputcsv($handle, ['عدد الموردين', $data['supplierSummary']['suppliers_count']]);
            fputcsv($handle, ['فواتير الموردين المفتوحة', $data['supplierSummary']['invoice_count']]);
            fputcsv($handle, ['إجمالي ذمم الموردين المفتوحة', number_format((float) $data['supplierSummary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر للموردين', number_format((float) $data['supplierSummary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['صافي الذمم']);
            fputcsv($handle, ['صافي الذمم المفتوحة', number_format((float) $data['netSummary']['net_open_total'], 2, '.', '')]);
            fputcsv($handle, ['حالة صافي الذمم', $data['netSummary']['position_label']]);
            fputcsv($handle, ['صافي المتأخرات', number_format((float) $data['netSummary']['net_overdue_total'], 2, '.', '')]);
            fputcsv($handle, ['حالة صافي المتأخرات', $data['netSummary']['overdue_position_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['مقارنة شرائح الأعمار']);
            fputcsv($handle, ['شريحة العمر', 'ذمم العملاء', 'ذمم الموردين', 'صافي الفرق']);

            foreach ($data['bucketComparison'] as $bucket) {
                fputcsv($handle, [
                    $bucket['label'],
                    number_format((float) $bucket['customer_total'], 2, '.', ''),
                    number_format((float) $bucket['supplier_total'], 2, '.', ''),
                    number_format((float) $bucket['net_total'], 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function dashboardData(array $customerAging, array $supplierAging, Request $request): array
    {
        $selectedBranchId = $request->integer('branch_id') ?: null;
        $selectedAsOfDateParam = $this->dateInput($request, 'as_of_date');
        $selectedAsOfDate = $selectedAsOfDateParam ?: now()->format('Y-m-d');

        $branches = DB::table('branches')->orderBy('name')->get(['id', 'name']);
        $selectedBranchName = $selectedBranchId
            ? optional($branches->firstWhere('id', $selectedBranchId))->name
            : null;

        $customerRemainingTotal = round((float) $customerAging['summary']['remaining_total'], 2);
        $supplierRemainingTotal = round((float) $supplierAging['summary']['remaining_total'], 2);
        $customerOverdueTotal = round((float) $customerAging['summary']['overdue_total'], 2);
        $supplierOverdueTotal = round((float) $supplierAging['summary']['overdue_total'], 2);

        $netOpenTotal = round($customerRemainingTotal - $supplierRemainingTotal, 2);
        $netOverdueTotal = round($customerOverdueTotal - $supplierOverdueTotal, 2);

        $filterParams = array_filter([
            'branch_id' => $selectedBranchId,
            'as_of_date' => $selectedAsOfDateParam,
        ]);

        return [
            'reportDate' => $this->reportDate($request),
            'branches' => $branches,
            'selectedBranchId' => $selectedBranchId,
            'selectedBranchName' => $selectedBranchName,
            'selectedAsOfDate' => $selectedAsOfDate,
            'filterParams' => $filterParams,
            'drilldownParams' => $filterParams,
            'customerSummary' => $customerAging['summary'],
            'supplierSummary' => $supplierAging['summary'],
            'netSummary' => [
                'net_open_total' => $netOpenTotal,
                'position_label' => $netOpenTotal >= 0 ? 'صافي لصالح الشركة' : 'صافي لصالح الموردين',
                'net_overdue_total' => $netOverdueTotal,
                'overdue_position_label' => $netOverdueTotal >= 0 ? 'متأخرات لصالح الشركة' : 'متأخرات لصالح الموردين',
            ],
            'bucketComparison' => $this->bucketComparison($customerAging['rows'], $supplierAging['rows']),
        ];
    }

    private function reportDate(Request $request): Carbon
    {
        $asOfDate = $this->dateInput($request, 'as_of_date');

        if ($asOfDate) {
            return Carbon::parse($asOfDate)->startOfDay();
        }

        return now()->startOfDay();
    }

    private function dateInput(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function bucketComparison(Collection $customerRows, Collection $supplierRows): array
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
                $customerTotal = round((float) $customerRows->sum(fn ($row) => (float) $row[$key]), 2);
                $supplierTotal = round((float) $supplierRows->sum(fn ($row) => (float) $row[$key]), 2);

                return [
                    'key' => $key,
                    'label' => $label,
                    'customer_total' => $customerTotal,
                    'supplier_total' => $supplierTotal,
                    'net_total' => round($customerTotal - $supplierTotal, 2),
                ];
            })
            ->values()
            ->all();
    }
}
