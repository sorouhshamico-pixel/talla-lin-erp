# Phase 35 — Cash Flow Dashboard

## Status

Completed and approved.

## Purpose

Phase 35 added a cash flow dashboard that summarizes expected cash inflows from customer receivables and expected cash outflows from supplier payables. The dashboard reuses approved customer and supplier aging report builders to keep calculations consistent across financial reports.

## Completed Phases

### Phase 35A — Cash Flow Dashboard Skeleton

Main output:

- CashFlowDashboardController
- Cash flow dashboard route
- Cash flow dashboard view
- Link from reports center
- Initial feature test

Files added:

- app/Http/Controllers/CashFlowDashboardController.php
- resources/views/reports/cash-flow-dashboard.blade.php
- tests/Feature/CashFlowDashboardTest.php

Route:

- reports.cash-flow-dashboard.index

Commit:

- cf0d9fa Add cash flow dashboard skeleton

### Phase 35B — Add Cash Flow Bucket Comparison

Added cash flow comparison by aging bucket.

Table columns:

- شريحة العمر
- تدفقات داخلة متوقعة
- تدفقات خارجة متوقعة
- صافي التدفق النقدي

Aging buckets:

- غير مستحقة بعد
- متأخرة 1 إلى 30
- متأخرة 31 إلى 60
- متأخرة 61 إلى 90
- أكثر من 90
- بدون تاريخ استحقاق

Commit:

- 6cc1762 Add cash flow bucket comparison

### Phase 35C — Add Cash Flow Risk Cards

Added risk cards to identify cash pressure and coverage.

Cards added:

- إجمالي التدفقات الداخلة المتأخرة
- إجمالي التدفقات الخارجة المتأخرة
- صافي الضغط النقدي المتأخر
- حالة الضغط النقدي
- نسبة تغطية الالتزامات المتوقعة
- حالة التغطية النقدية

Risk calculations:

- net_overdue_pressure = overdue_outflows minus overdue_inflows
- cash_coverage_ratio = expected_inflows divided by expected_outflows multiplied by 100

Risk labels:

- ضغط نقدي متأخر على الشركة
- المتأخرات الداخلة تغطي الالتزامات المتأخرة
- تغطية نقدية متوقعة كافية
- تغطية نقدية متوقعة غير كافية
- لا توجد التزامات خارجة مفتوحة

Commit:

- 8a76fc3 Add cash flow risk cards

### Phase 35D — Export Cash Flow Dashboard CSV

Added CSV export for the cash flow dashboard.

Route:

- reports.cash-flow-dashboard.export

Controller method:

- CashFlowDashboardController::export

View update:

- Added CSV export link to resources/views/reports/cash-flow-dashboard.blade.php

Test added:

- tests/Feature/CashFlowDashboardExportTest.php

CSV includes:

- Dashboard title
- Export generation date
- Report date
- Inflow summary
- Outflow summary
- Net cash flow
- Cash flow risk summary
- Cash flow by aging bucket

Commit:

- d7924eb Add cash flow dashboard CSV export

### Phase 35E — Print Cash Flow Dashboard

Added print view for the cash flow dashboard.

Route:

- reports.cash-flow-dashboard.print

Controller method:

- CashFlowDashboardController::print

Views:

- resources/views/reports/cash-flow-dashboard.blade.php
- resources/views/reports/cash-flow-dashboard-print.blade.php

Test added:

- tests/Feature/CashFlowDashboardPrintTest.php

Print view includes:

- Dashboard title
- Report date
- Inflow summary
- Outflow summary
- Net cash flow
- Cash flow risk summary
- Cash flow by aging bucket
- Browser print button
- Print-friendly CSS

Commit:

- d90784e Add cash flow dashboard print view

## Main Files

Controller:

- app/Http/Controllers/CashFlowDashboardController.php

Views:

- resources/views/reports/cash-flow-dashboard.blade.php
- resources/views/reports/cash-flow-dashboard-print.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/CashFlowDashboardTest.php
- tests/Feature/CashFlowDashboardExportTest.php
- tests/Feature/CashFlowDashboardPrintTest.php

Related services:

- app/Services/CustomerSalesInvoiceAgingReportBuilder.php
- app/Services/SupplierPurchaseInvoiceAgingReportBuilder.php

## Routes

Main dashboard:

- reports.cash-flow-dashboard.index

CSV export:

- reports.cash-flow-dashboard.export

Print view:

- reports.cash-flow-dashboard.print

## Data Sources

Customer inflows source:

- CustomerSalesInvoiceAgingReportBuilder

Supplier outflows source:

- SupplierPurchaseInvoiceAgingReportBuilder

The dashboard intentionally reuses existing aging builders instead of duplicating invoice-aging logic.

## Dashboard Data

Inflow summary:

- customers_count
- open_invoice_count
- expected_inflows
- overdue_inflows

Outflow summary:

- suppliers_count
- open_invoice_count
- expected_outflows
- overdue_outflows

Net cash summary:

- net_expected_cash
- position_label

Risk summary:

- overdue_inflows
- overdue_outflows
- net_overdue_pressure
- cash_coverage_ratio
- pressure_label
- coverage_label

Bucket cash flow:

- label
- expected_inflows
- expected_outflows
- net_cash_flow

## Calculation Rules

Expected inflows:

- Total remaining amount from open customer sales invoices.

Expected outflows:

- Total remaining amount from open supplier purchase invoices.

Net expected cash:

- expected_inflows minus expected_outflows.

Net overdue pressure:

- overdue_outflows minus overdue_inflows.

Cash coverage ratio:

- expected_inflows divided by expected_outflows multiplied by 100.
- If expected_outflows is zero, ratio is treated as not applicable.

## Final Confirmed Test

Last confirmed full test after Phase 35E:

php artisan test = 628 passed / 4573 assertions

## Notes For Future Development

The next logical improvements can be:

- Add branch filter to the cash flow dashboard
- Add date range filter
- Add drill-down links from bucket rows to invoice details
- Add PDF export
- Add dashboard cards to the main homepage
- Add scheduled cash flow summary
- Add actual bank and cash account balances when treasury module exists
