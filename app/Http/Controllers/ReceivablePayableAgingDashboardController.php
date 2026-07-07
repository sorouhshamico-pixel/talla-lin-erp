<?php

namespace App\Http\Controllers;

use App\Services\CustomerSalesInvoiceAgingReportBuilder;
use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

        return view('reports.receivable-payable-aging-dashboard', [
            'reportDate' => now()->startOfDay(),
            'customerSummary' => $customerAging['summary'],
            'supplierSummary' => $supplierAging['summary'],
            'bucketComparison' => $this->bucketComparison($customerAging['rows'], $supplierAging['rows']),
            'netSummary' => $this->netSummary($customerAging['summary'], $supplierAging['summary']),
        ]);
    }

    public function print(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ): View {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        return view('reports.receivable-payable-aging-dashboard-print', [
            'reportDate' => now()->startOfDay(),
            'customerSummary' => $customerAging['summary'],
            'supplierSummary' => $supplierAging['summary'],
            'bucketComparison' => $this->bucketComparison($customerAging['rows'], $supplierAging['rows']),
            'netSummary' => $this->netSummary($customerAging['summary'], $supplierAging['summary']),
        ]);
    }

    public function export(
        Request $request,
        CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ) {
        $customerAging = $customerAgingBuilder->build($request);
        $supplierAging = $supplierAgingBuilder->build($request);

        $reportDate = now()->startOfDay();
        $bucketComparison = $this->bucketComparison($customerAging['rows'], $supplierAging['rows']);
        $netSummary = $this->netSummary($customerAging['summary'], $supplierAging['summary']);

        $fileName = 'receivable-payable-aging-dashboard-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($reportDate, $customerAging, $supplierAging, $bucketComparison, $netSummary) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, chr(239) . chr(187) . chr(191));

            fputcsv($handle, ['لوحة أعمار الذمم']);
            fputcsv($handle, ['تاريخ إنشاء التقرير', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, ['تاريخ التقرير', $reportDate->format('Y-m-d')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص ذمم العملاء']);
            fputcsv($handle, ['عدد العملاء', $customerAging['summary']['customers_count']]);
            fputcsv($handle, ['عدد فواتير العملاء المفتوحة', $customerAging['summary']['invoice_count']]);
            fputcsv($handle, ['إجمالي ذمم العملاء المفتوحة', number_format((float) $customerAging['summary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر على العملاء', number_format((float) $customerAging['summary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['ملخص ذمم الموردين']);
            fputcsv($handle, ['عدد الموردين', $supplierAging['summary']['suppliers_count']]);
            fputcsv($handle, ['عدد فواتير الموردين المفتوحة', $supplierAging['summary']['invoice_count']]);
            fputcsv($handle, ['إجمالي ذمم الموردين المفتوحة', number_format((float) $supplierAging['summary']['remaining_total'], 2, '.', '')]);
            fputcsv($handle, ['إجمالي المتأخر للموردين', number_format((float) $supplierAging['summary']['overdue_total'], 2, '.', '')]);
            fputcsv($handle, []);

            fputcsv($handle, ['صافي الذمم']);
            fputcsv($handle, ['صافي الذمم المفتوحة', number_format((float) $netSummary['net_open_total'], 2, '.', '')]);
            fputcsv($handle, ['حالة صافي الذمم', $netSummary['position_label']]);
            fputcsv($handle, ['صافي المتأخرات', number_format((float) $netSummary['net_overdue_total'], 2, '.', '')]);
            fputcsv($handle, ['حالة صافي المتأخرات', $netSummary['overdue_position_label']]);
            fputcsv($handle, []);

            fputcsv($handle, ['مقارنة شرائح الأعمار']);
            fputcsv($handle, ['شريحة العمر', 'ذمم العملاء', 'ذمم الموردين', 'صافي الفرق']);

            foreach ($bucketComparison as $bucket) {
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

    private function netSummary(array $customerSummary, array $supplierSummary): array
    {
        $netOpen = round((float) $customerSummary['remaining_total'] - (float) $supplierSummary['remaining_total'], 2);
        $netOverdue = round((float) $customerSummary['overdue_total'] - (float) $supplierSummary['overdue_total'], 2);

        return [
            'net_open_total' => $netOpen,
            'net_overdue_total' => $netOverdue,
            'position_label' => $netOpen >= 0 ? 'صافي لصالح الشركة' : 'صافي مستحق على الشركة',
            'overdue_position_label' => $netOverdue >= 0 ? 'متأخرات لصالح الشركة' : 'متأخرات مستحقة على الشركة',
        ];
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
