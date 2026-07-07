<?php

namespace App\Http\Controllers;

use App\Services\CustomerSalesInvoiceAgingReportBuilder;
use App\Services\SupplierPurchaseInvoiceAgingReportBuilder;
use Illuminate\Http\Request;
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
        ]);
    }
}
