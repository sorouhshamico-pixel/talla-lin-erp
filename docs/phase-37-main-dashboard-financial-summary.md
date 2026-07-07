# Phase 37 — Main Dashboard Financial Summary

## Status

Completed and approved.

## Purpose

Phase 37 added a financial summary section to the main dashboard. The section gives a quick operational view of customer receivables, supplier payables, expected cash flow, financial risk indicators, and direct report actions.

## Completed Phases

### Phase 37A — Add Financial Summary Cards To Main Dashboard

Added the first financial summary cards to the main dashboard.

Main output:

- FinancialDashboardSummaryService
- Dashboard financial summary partial
- Main dashboard integration
- Main dashboard financial summary test

Files added:

- app/Services/FinancialDashboardSummaryService.php
- resources/views/dashboard/_financial-summary.blade.php
- tests/Feature/MainDashboardFinancialSummaryTest.php

Cards added:

- ذمم العملاء المفتوحة
- التزامات الموردين المفتوحة
- صافي التدفق النقدي المتوقع
- حالة التدفق النقدي
- فواتير العملاء المفتوحة
- فواتير الموردين المفتوحة
- متأخرات العملاء
- متأخرات الموردين

Commit:

- b42b856 Add financial cards to main dashboard

### Phase 37B — Add Financial Risk Cards To Main Dashboard

Added financial risk indicators to the main dashboard summary.

Service updates:

- net_overdue_pressure
- cash_coverage_ratio
- cash_coverage_label
- risk_label

Risk cards added:

- صافي الضغط النقدي المتأخر
- نسبة تغطية الالتزامات
- حالة التغطية النقدية
- مؤشر المتابعة المالية

Direct links added:

- تفاصيل فواتير العملاء
- تفاصيل فواتير الموردين

Test added:

- tests/Feature/MainDashboardFinancialRiskCardsTest.php

Commit:

- b6a45e1 Add financial risk cards to main dashboard

### Phase 37C — Export Main Dashboard Financial Summary CSV

Added CSV export for the main dashboard financial summary.

Controller added:

- app/Http/Controllers/FinancialDashboardSummaryExportController.php

Route added:

- dashboard.financial-summary.export

View update:

- Added export link to resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardFinancialSummaryExportTest.php

CSV includes:

- Report title
- Export generation date
- Customer receivables summary
- Supplier payables summary
- Expected cash flow summary
- Financial risk indicators

Commit:

- d2c4c6d Add main dashboard financial summary export

### Phase 37D — Print Main Dashboard Financial Summary

Added print view for the main dashboard financial summary.

Controller added:

- app/Http/Controllers/FinancialDashboardSummaryPrintController.php

Route added:

- dashboard.financial-summary.print

View added:

- resources/views/dashboard/financial-summary-print.blade.php

View update:

- Added print link to resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardFinancialSummaryPrintTest.php

Print view includes:

- Customer receivables summary
- Supplier payables summary
- Expected cash flow summary
- Financial risk indicators
- Browser print button
- Print-friendly CSS

Commit:

- 5b71c01 Add main dashboard financial summary print view

## Main Files

Service:

- app/Services/FinancialDashboardSummaryService.php

Controllers:

- app/Http/Controllers/FinancialDashboardSummaryExportController.php
- app/Http/Controllers/FinancialDashboardSummaryPrintController.php

Views:

- resources/views/dashboard/_financial-summary.blade.php
- resources/views/dashboard/financial-summary-print.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/MainDashboardFinancialSummaryTest.php
- tests/Feature/MainDashboardFinancialRiskCardsTest.php
- tests/Feature/MainDashboardFinancialSummaryExportTest.php
- tests/Feature/MainDashboardFinancialSummaryPrintTest.php

## Routes

CSV export:

- dashboard.financial-summary.export

Print view:

- dashboard.financial-summary.print

Related routes linked from dashboard:

- reports.cash-flow-dashboard.index
- reports.receivable-payable-aging-dashboard.index
- reports.customer-sales-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.drilldown
- reports.index

## Data Sources

The dashboard financial summary uses:

- CustomerSalesInvoiceAgingReportBuilder
- SupplierPurchaseInvoiceAgingReportBuilder

This keeps the main dashboard consistent with the approved aging reports and cash flow dashboard.

## Summary Fields

Customer receivables:

- customers_count
- customer_open_invoice_count
- expected_inflows
- overdue_inflows

Supplier payables:

- suppliers_count
- supplier_open_invoice_count
- expected_outflows
- overdue_outflows

Cash flow:

- net_expected_cash
- position_label

Risk indicators:

- net_overdue_pressure
- cash_coverage_ratio
- cash_coverage_label
- risk_label

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

## Risk Labels

Coverage label:

- لا توجد التزامات موردين مفتوحة
- تغطية نقدية متوقعة كافية
- تغطية نقدية متوقعة غير كافية

Risk label:

- لا توجد التزامات موردين مفتوحة
- يتطلب متابعة نقدية
- يوجد ضغط متأخر
- تغطية نقدية غير كافية
- الوضع المالي المتوقع مستقر

## Final Confirmed Test

Last confirmed full test after Phase 37D:

php artisan test = 650 passed / 4737 assertions

## Notes For Future Development

The next logical improvements can be:

- Add dashboard branch filter
- Add date range filter
- Add monthly cash trend cards
- Add top overdue customers widget
- Add top overdue suppliers widget
- Add chart cards for receivables and payables
- Add cached dashboard summary if data volume grows
- Add permission-based visibility for financial cards
