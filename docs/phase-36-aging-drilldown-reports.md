# Phase 36 — Aging Drilldown Reports

## Status

Completed and approved.

## Purpose

Phase 36 added drilldown pages for open customer sales invoices and open supplier purchase invoices. These pages allow users to move from summary aging dashboards to invoice-level details by aging bucket and party filter.

## Completed Phases

### Phase 36A — Customer Open Sales Invoice Drilldown

Added a detailed drilldown page for open customer sales invoices.

Main output:

- Customer sales invoice aging drilldown controller
- Customer open invoice drilldown route
- Customer drilldown view
- Link from customer sales invoice aging report
- Feature test

Files added:

- app/Http/Controllers/CustomerSalesInvoiceAgingDrilldownController.php
- resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
- tests/Feature/CustomerSalesInvoiceAgingDrilldownTest.php

Route:

- reports.customer-sales-invoice-aging.drilldown

Commit:

- 160d504 Add customer aging invoice drilldown

### Phase 36B — Supplier Open Purchase Invoice Drilldown

Added a detailed drilldown page for open supplier purchase invoices.

Main output:

- Supplier purchase invoice aging drilldown controller
- Supplier open invoice drilldown route
- Supplier drilldown view
- Link from supplier purchase invoice aging report
- Feature test

Files added:

- app/Http/Controllers/SupplierPurchaseInvoiceAgingDrilldownController.php
- resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
- tests/Feature/SupplierPurchaseInvoiceAgingDrilldownTest.php

Route:

- reports.supplier-purchase-invoice-aging.drilldown

Commit:

- d525dd4 Add supplier aging invoice drilldown

### Phase 36C — Add Drilldown Links To Aging Dashboard Buckets

Added drilldown links from the receivable and payable aging dashboard bucket comparison table.

Dashboard:

- resources/views/reports/receivable-payable-aging-dashboard.blade.php

Links added:

- Customer aging bucket amount links to customer invoice drilldown
- Supplier aging bucket amount links to supplier invoice drilldown

Updated test:

- tests/Feature/ReceivablePayableAgingDashboardTest.php

Commit:

- 6653633 Add aging dashboard drilldown links

### Phase 36D — Add Drilldown Links To Cash Flow Dashboard Buckets

Added drilldown links from the cash flow dashboard bucket comparison table.

Dashboard:

- resources/views/reports/cash-flow-dashboard.blade.php

Links added:

- Expected inflow bucket links to customer invoice drilldown
- Expected outflow bucket links to supplier invoice drilldown

Updated test:

- tests/Feature/CashFlowDashboardTest.php

Commit:

- 2c7cc9b Add cash flow dashboard drilldown links

### Phase 36E — Export Customer And Supplier Drilldown CSV

Added CSV exports for customer and supplier drilldown pages.

Routes:

- reports.customer-sales-invoice-aging.drilldown.export
- reports.supplier-purchase-invoice-aging.drilldown.export

Controller methods:

- CustomerSalesInvoiceAgingDrilldownController::export
- SupplierPurchaseInvoiceAgingDrilldownController::export

Views updated:

- resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
- resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php

Test added:

- tests/Feature/AgingDrilldownExportTest.php

CSV includes:

- Report title
- Export generation date
- Report date
- Party filter label
- Aging bucket filter label
- Summary
- Invoice table

Commit:

- 02cbcd6 Add aging drilldown CSV exports

## Main Files

Controllers:

- app/Http/Controllers/CustomerSalesInvoiceAgingDrilldownController.php
- app/Http/Controllers/SupplierPurchaseInvoiceAgingDrilldownController.php

Views:

- resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
- resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
- resources/views/reports/receivable-payable-aging-dashboard.blade.php
- resources/views/reports/cash-flow-dashboard.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/CustomerSalesInvoiceAgingDrilldownTest.php
- tests/Feature/SupplierPurchaseInvoiceAgingDrilldownTest.php
- tests/Feature/AgingDrilldownExportTest.php
- tests/Feature/ReceivablePayableAgingDashboardTest.php
- tests/Feature/CashFlowDashboardTest.php

## Routes

Customer drilldown:

- reports.customer-sales-invoice-aging.drilldown

Customer drilldown CSV export:

- reports.customer-sales-invoice-aging.drilldown.export

Supplier drilldown:

- reports.supplier-purchase-invoice-aging.drilldown

Supplier drilldown CSV export:

- reports.supplier-purchase-invoice-aging.drilldown.export

## Supported Filters

Customer drilldown filters:

- customer_id
- aging_bucket

Supplier drilldown filters:

- supplier_id
- aging_bucket

Supported aging buckets:

- not_due
- overdue_1_30
- overdue_31_60
- overdue_61_90
- overdue_more_than_90
- without_due_date

## Drilldown Tables

Customer drilldown table columns:

- رقم الفاتورة
- العميل
- تاريخ الإصدار
- تاريخ الاستحقاق
- الإجمالي
- المدفوع
- المتبقي
- حالة الدفع

Supplier drilldown table columns:

- رقم الفاتورة
- المورد
- تاريخ الإصدار
- تاريخ الاستحقاق
- الإجمالي
- المدفوع
- المتبقي
- حالة الدفع

## Calculation Rules

Customer drilldown includes sales invoices where:

- remaining_amount > 0

Supplier drilldown includes purchase invoices where:

- remaining_amount > 0

Summary fields:

- invoice_count
- grand_total
- paid_total
- remaining_total

## Integration Points

Customer aging report:

- Links to customer invoice drilldown with current filters.

Supplier aging report:

- Links to supplier invoice drilldown with current filters.

Receivable and payable aging dashboard:

- Customer bucket totals link to customer drilldown.
- Supplier bucket totals link to supplier drilldown.

Cash flow dashboard:

- Expected inflow bucket totals link to customer drilldown.
- Expected outflow bucket totals link to supplier drilldown.

## Final Confirmed Test

Last confirmed full test after Phase 36E:

php artisan test = 642 passed / 4663 assertions

## Notes For Future Development

The next logical improvements can be:

- Add print views for drilldown pages
- Add invoice show links inside drilldown tables
- Add branch filters
- Add date range filters
- Add PDF export
- Add pagination if invoice volume becomes large
- Add drilldown widgets on the main dashboard
