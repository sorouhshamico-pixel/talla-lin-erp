# Phase 38 — Top Overdue Dashboard Widgets

## Status

Completed and approved.

## Purpose

Phase 38 added top overdue customer and supplier widgets to the main dashboard. These widgets help identify the highest overdue receivables and payables directly from the dashboard, with links to drilldown reports, CSV exports, and print views.

## Completed Phases

### Phase 38A — Top Overdue Customers Widget

Added a dashboard widget for the highest overdue customers.

Service method added:

- FinancialDashboardSummaryService::topOverdueCustomers

Dashboard widget:

- أكبر العملاء المتأخرين

Widget columns:

- العميل
- عدد الفواتير
- إجمالي المتأخر
- أقدم استحقاق
- أقصى تأخير
- التفاصيل

View updated:

- resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardTopOverdueCustomersTest.php

Commit:

- cc6ecb0 Add top overdue customers dashboard widget

### Phase 38B — Top Overdue Suppliers Widget

Added a dashboard widget for the highest overdue suppliers.

Service method added:

- FinancialDashboardSummaryService::topOverdueSuppliers

Dashboard widget:

- أكبر الموردين المتأخرين

Widget columns:

- المورد
- عدد الفواتير
- إجمالي المتأخر
- أقدم استحقاق
- أقصى تأخير
- التفاصيل

View updated:

- resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardTopOverdueSuppliersTest.php

Commit:

- d7c11e9 Add top overdue suppliers dashboard widget

### Phase 38C — Export Top Overdue Customers And Suppliers CSV

Added CSV exports for the top overdue customer and supplier widgets.

Controllers added:

- app/Http/Controllers/MainDashboardTopOverdueCustomersExportController.php
- app/Http/Controllers/MainDashboardTopOverdueSuppliersExportController.php

Routes added:

- dashboard.top-overdue-customers.export
- dashboard.top-overdue-suppliers.export

View updated:

- resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardTopOverdueExportTest.php

CSV exports include:

- Report title
- Export generation date
- Party name
- Invoice count
- Overdue total
- Oldest due date
- Maximum days overdue

Commit:

- 19efe61 Add top overdue dashboard widget exports

### Phase 38D — Print Top Overdue Customers And Suppliers

Added a print view for top overdue customers and suppliers.

Controller added:

- app/Http/Controllers/MainDashboardTopOverduePrintController.php

Route added:

- dashboard.top-overdue.print

View added:

- resources/views/dashboard/top-overdue-print.blade.php

View updated:

- resources/views/dashboard/_financial-summary.blade.php

Test added:

- tests/Feature/MainDashboardTopOverduePrintTest.php

Print view includes:

- Top overdue customers table
- Top overdue suppliers table
- Empty states for both widgets
- Browser print button
- Print-friendly CSS

Commit:

- b53e0a4 Add top overdue dashboard widget print view

## Main Files

Service:

- app/Services/FinancialDashboardSummaryService.php

Controllers:

- app/Http/Controllers/MainDashboardTopOverdueCustomersExportController.php
- app/Http/Controllers/MainDashboardTopOverdueSuppliersExportController.php
- app/Http/Controllers/MainDashboardTopOverduePrintController.php

Views:

- resources/views/dashboard/_financial-summary.blade.php
- resources/views/dashboard/top-overdue-print.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/MainDashboardTopOverdueCustomersTest.php
- tests/Feature/MainDashboardTopOverdueSuppliersTest.php
- tests/Feature/MainDashboardTopOverdueExportTest.php
- tests/Feature/MainDashboardTopOverduePrintTest.php

## Routes

Top overdue customers export:

- dashboard.top-overdue-customers.export

Top overdue suppliers export:

- dashboard.top-overdue-suppliers.export

Top overdue print view:

- dashboard.top-overdue.print

Related drilldown routes:

- reports.customer-sales-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.drilldown

## Data Sources

Top overdue customers:

- SalesInvoice records where remaining_amount is greater than zero
- due_at is not null
- due_at is before the report date

Top overdue suppliers:

- PurchaseInvoice records where remaining_amount is greater than zero
- due_at is not null
- due_at is before the report date

## Calculation Rules

Overdue total:

- Sum of remaining_amount for overdue invoices grouped by customer or supplier.

Invoice count:

- Count of overdue open invoices for the customer or supplier.

Oldest due date:

- Earliest due_at date inside the grouped overdue invoices.

Maximum days overdue:

- Difference in days between report date and oldest due date.

Sorting:

- Rows are sorted by overdue_total descending.

Dashboard limit:

- Dashboard widgets show top 5 rows.

Export and print limit:

- Export and print views use top 50 rows.

## Dashboard Integration

The widgets are displayed inside:

- resources/views/dashboard/_financial-summary.blade.php

Customer widget actions:

- Export customers CSV
- View overdue customer drilldown
- Open customer-specific invoice drilldown

Supplier widget actions:

- Export suppliers CSV
- View overdue supplier drilldown
- Open supplier-specific invoice drilldown

Shared action:

- Print top overdue customers and suppliers

## Final Confirmed Test

Last confirmed full test after Phase 38D:

php artisan test = 662 passed / 4823 assertions

## Notes For Future Development

The next logical improvements can be:

- Add branch filter to top overdue widgets
- Add date range filter
- Add payment follow-up status
- Add collection priority labels
- Add supplier payment priority labels
- Add aging bucket breakdown inside each widget row
- Add cached widget summaries for large datasets
- Add permissions for dashboard financial widgets
