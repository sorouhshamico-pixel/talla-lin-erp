<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierPurchaseInvoiceAgingReportController extends Controller
{
    public function index(Request $request): View
    {
        return view('reports.supplier-purchase-invoice-aging', [
            'reportDate' => now()->toDateString(),
            'supplierFilter' => $request->input('supplier_id'),
            'agingBucketFilter' => $request->input('aging_bucket'),
        ]);
    }
}
