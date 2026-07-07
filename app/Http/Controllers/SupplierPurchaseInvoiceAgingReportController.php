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
        ]);
    }
}
