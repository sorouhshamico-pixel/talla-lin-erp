<?php

namespace App\Services;

use Illuminate\Http\Request;

class FinancialDashboardSummaryService
{
    public function __construct(
        private readonly CustomerSalesInvoiceAgingReportBuilder $customerAgingBuilder,
        private readonly SupplierPurchaseInvoiceAgingReportBuilder $supplierAgingBuilder
    ) {
    }

    public function summary(?Request $request = null): array
    {
        $request ??= request();

        $customerAging = $this->customerAgingBuilder->build($request);
        $supplierAging = $this->supplierAgingBuilder->build($request);

        $expectedInflows = round((float) $customerAging['summary']['remaining_total'], 2);
        $expectedOutflows = round((float) $supplierAging['summary']['remaining_total'], 2);
        $overdueInflows = round((float) $customerAging['summary']['overdue_total'], 2);
        $overdueOutflows = round((float) $supplierAging['summary']['overdue_total'], 2);
        $netExpectedCash = round($expectedInflows - $expectedOutflows, 2);

        return [
            'customers_count' => $customerAging['summary']['customers_count'],
            'customer_open_invoice_count' => $customerAging['summary']['invoice_count'],
            'expected_inflows' => $expectedInflows,
            'overdue_inflows' => $overdueInflows,

            'suppliers_count' => $supplierAging['summary']['suppliers_count'],
            'supplier_open_invoice_count' => $supplierAging['summary']['invoice_count'],
            'expected_outflows' => $expectedOutflows,
            'overdue_outflows' => $overdueOutflows,

            'net_expected_cash' => $netExpectedCash,
            'position_label' => $netExpectedCash >= 0
                ? 'صافي تدفق نقدي متوقع لصالح الشركة'
                : 'صافي التزامات نقدية متوقعة على الشركة',
        ];
    }
}
