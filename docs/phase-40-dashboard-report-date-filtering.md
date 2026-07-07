# Phase 40 — Dashboard Report Date Filtering

## Status

Completed and approved.

## Purpose

Phase 40 added report date filtering to the main dashboard financial widgets and connected report actions. The report date filter allows users to calculate overdue amounts, top overdue parties, and aging drilldown results as of a selected date.

## Query Parameter

The report date filter uses:

- as_of_date

Expected format:

- YYYY-MM-DD

When as_of_date is present and valid, overdue calculations use that date.

When as_of_date is absent or invalid, the system falls back to the current date.

## Completed Phases

### Phase 40A — Add Report Date Filter To Main Dashboard Financial Widgets

Added as_of_date support to the main dashboard financial widgets.

Main updates:

- Added as_of_date handling to FinancialDashboardSummaryService.
- Added report date input to the main dashboard financial summary section.
- Preserved as_of_date in dashboard action links.
- Applied as_of_date to overdue customer and supplier calculations.
- Applied as_of_date to top overdue customer and supplier widgets.

Files updated:

- app/Services/FinancialDashboardSummaryService.php
- resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardFinancialReportDateFilterTest.php

Commit:

- f7f6bb3 Add report date filter to main dashboard financial widgets

Confirmed full test:

- php artisan test = 674 passed / 4936 assertions

### Phase 40B — Add Report Date Context To Dashboard Exports And Print Views

Added report date context to dashboard exports and print views.

Main updates:

- Added as_of_date context to financial summary CSV export.
- Added as_of_date context to financial summary print view.
- Added as_of_date context to top overdue customers CSV export.
- Added as_of_date context to top overdue suppliers CSV export.
- Added as_of_date context to top overdue print view.
- Ensured exports and print views calculate overdue values using as_of_date.

Controllers updated:

- app/Http/Controllers/FinancialDashboardSummaryExportController.php
- app/Http/Controllers/FinancialDashboardSummaryPrintController.php
- app/Http/Controllers/MainDashboardTopOverdueCustomersExportController.php
- app/Http/Controllers/MainDashboardTopOverdueSuppliersExportController.php
- app/Http/Controllers/MainDashboardTopOverduePrintController.php

Views updated:

- resources/views/dashboard/financial-summary-print.blade.php
- resources/views/dashboard/top-overdue-print.blade.php

Test added:

- tests/Feature/MainDashboardReportDateContextExportPrintTest.php

Commit:

- 5f5de96 Add report date context to dashboard exports and print views

Confirmed full test:

- php artisan test = 678 passed / 4966 assertions

### Phase 40C — Add Report Date Filter To Aging Drilldown Reports

Added as_of_date support to customer and supplier aging drilldown reports.

Main updates:

- Added as_of_date support to customer sales invoice aging drilldown.
- Added as_of_date support to supplier purchase invoice aging drilldown.
- Added report date input to both drilldown pages.
- Added selected report date context to both drilldown pages.
- Preserved as_of_date in drilldown export links.
- Preserved as_of_date in dashboard links to aging drilldown reports.
- Ensured aging buckets are calculated using as_of_date.

Controllers updated:

- app/Http/Controllers/CustomerSalesInvoiceAgingDrilldownController.php
- app/Http/Controllers/SupplierPurchaseInvoiceAgingDrilldownController.php

Views updated:

- resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
- resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
- resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/AgingDrilldownReportDateFilterTest.php

Commit:

- f2cf8e8 Add report date filter to aging drilldown reports

Confirmed full test:

- php artisan test = 682 passed / 5002 assertions

## Current Report Date Filtering Scope

The as_of_date filter now applies to:

- Main dashboard financial summary cards
- Main dashboard financial risk cards
- Top overdue customers widget
- Top overdue suppliers widget
- Financial summary CSV export
- Financial summary print view
- Top overdue customers CSV export
- Top overdue suppliers CSV export
- Top overdue print view
- Customer sales invoice aging drilldown
- Supplier purchase invoice aging drilldown
- Customer drilldown CSV export
- Supplier drilldown CSV export

## Main Routes Affected

Dashboard:

- dashboard

Dashboard exports and print:

- dashboard.financial-summary.export
- dashboard.financial-summary.print
- dashboard.top-overdue-customers.export
- dashboard.top-overdue-suppliers.export
- dashboard.top-overdue.print

Aging drilldowns:

- reports.customer-sales-invoice-aging.drilldown
- reports.customer-sales-invoice-aging.drilldown.export
- reports.supplier-purchase-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.drilldown.export

## Calculation Rules

Expected inflows and expected outflows:

- Continue to include all open remaining amounts.

Overdue inflows and overdue outflows:

- Calculated by comparing due_at against as_of_date.

Top overdue customers:

- Includes customer sales invoices with remaining_amount greater than zero.
- due_at must be before as_of_date.
- Rows are grouped by customer.
- Rows are sorted by overdue_total descending.

Top overdue suppliers:

- Includes supplier purchase invoices with remaining_amount greater than zero.
- due_at must be before as_of_date.
- Rows are grouped by supplier.
- Rows are sorted by overdue_total descending.

Aging drilldown buckets:

- not_due uses due_at greater than or equal to as_of_date.
- overdue_1_30 uses invoices overdue from 1 to 30 days as of as_of_date.
- overdue_31_60 uses invoices overdue from 31 to 60 days as of as_of_date.
- overdue_61_90 uses invoices overdue from 61 to 90 days as of as_of_date.
- overdue_more_than_90 uses invoices overdue more than 90 days as of as_of_date.
- without_due_date uses invoices without due_at.

## Branch And Report Date Combined Filtering

Phase 40 works with Phase 39 branch filtering.

Supported combined filters:

- branch_id
- as_of_date
- aging_bucket
- customer_id
- supplier_id

The dashboard and drilldown reports preserve these filters across links, exports, and print views where applicable.

## Final Confirmed Test

Last confirmed full test after Phase 40C:

- php artisan test = 682 passed / 5002 assertions

## Notes For Future Development

Recommended next improvements:

- Add date range filtering to cash flow dashboard.
- Add as_of_date support to receivable and payable aging dashboard if not already covered.
- Add user preference persistence for dashboard filters.
- Add quick date presets such as today, end of month, previous month, and quarter end.
- Add validation message for invalid as_of_date inputs.
- Add permissions for financial dashboard date-sensitive reports.
