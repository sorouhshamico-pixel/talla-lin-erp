<?php

namespace App\Http\Controllers;

use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPurchaseInvoiceAgingReportController extends Controller
{
    public function index(Request $request, SupplierPurchaseInvoiceAgingReportBuilder $builder): View
    {
        $report = $builder->build($request);

        return view('reports.supplier-purchase-invoice-aging', [
            'reportDate' => $report['reportDate'],
            'rows' => $report['rows'],
            'summary' => $report['summary'],
            'supplierFilterLabel' => $report['supplierFilterLabel'],
            'agingBucketFilterLabel' => $report['agingBucketFilterLabel'],
            'suppliers' => \App\Models\Supplier::query()->orderBy('name')->get(['id', 'name']),
            'agingBuckets' => [
                'not_due' => 'غير مستحقة بعد',
                'overdue_1_30' => 'متأخرة 1 إلى 30 يوم',
                'overdue_31_60' => 'متأخرة 31 إلى 60 يوم',
                'overdue_61_90' => 'متأخرة 61 إلى 90 يوم',
                'overdue_more_than_90' => 'أكثر من 90 يوم',
                'without_due_date' => 'بدون تاريخ استحقاق',
            ],
        ]);
    }
}
