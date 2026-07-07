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
        ]);
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
