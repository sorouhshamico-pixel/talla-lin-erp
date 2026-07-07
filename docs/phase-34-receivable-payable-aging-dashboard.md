# Phase 34 — Receivable Payable Aging Dashboard

## Status

Completed and approved.

## Purpose

Phase 34 added a consolidated aging dashboard for receivables and payables. The dashboard combines customer receivable aging and supplier payable aging summaries into one management view.

## Completed Phases

### Phase 34A — Receivables And Payables Aging Dashboard Skeleton

Main output:

- ReceivablePayableAgingDashboardController
- Dashboard route
- Dashboard view
- Link from reports center
- Initial feature test

Files added:

- app/Http/Controllers/ReceivablePayableAgingDashboardController.php
- resources/views/reports/receivable-payable-aging-dashboard.blade.php
- tests/Feature/ReceivablePayableAgingDashboardTest.php

Route:

- reports.receivable-payable-aging-dashboard.index

### Phase 34B — Add Aging Bucket Comparison To Dashboard

Added comparison table by aging bucket.

Comparison columns:

- شريحة العمر
- ذمم العملاء
- ذمم الموردين
- صافي الفرق

Aging buckets:

- غير مستحقة بعد
- متأخرة 1 إلى 30
- متأخرة 31 إلى 60
- متأخرة 61 إلى 90
- أكثر من 90
- بدون تاريخ استحقاق

### Phase 34C — Add Net Aging Position Cards

Added net position cards.

Cards added:

- صافي الذمم المفتوحة
- حالة صافي الذمم
- صافي المتأخرات
- حالة صافي المتأخرات

Net calculations:

- net_open_total = customer remaining_total minus supplier remaining_total
- net_overdue_total = customer overdue_total minus supplier overdue_total

Position labels:

- صافي لصالح الشركة
- صافي مستحق على الشركة
- متأخرات لصالح الشركة
- متأخرات مستحقة على الشركة

### Phase 34D — Export Receivable Payable Aging Dashboard CSV

Added CSV export for the dashboard.

Route:

- reports.receivable-payable-aging-dashboard.export

Controller method:

- ReceivablePayableAgingDashboardController::export

View update:

- Added CSV export link to resources/views/reports/receivable-payable-aging-dashboard.blade.php

Test added:

- tests/Feature/ReceivablePayableAgingDashboardExportTest.php

CSV includes:

- Dashboard title
- Export generation date
- Report date
- Customer receivables summary
- Supplier payables summary
- Net aging position
- Aging bucket comparison

### Phase 34E — Print Receivable Payable Aging Dashboard

Added print view for the dashboard.

Route:

- reports.receivable-payable-aging-dashboard.print

Controller method:

- ReceivablePayableAgingDashboardController::print

Views:

- resources/views/reports/receivable-payable-aging-dashboard-print.blade.php
- Print link added to resources/views/reports/receivable-payable-aging-dashboard.blade.php

Test added:

- tests/Feature/ReceivablePayableAgingDashboardPrintTest.php

Print view includes:

- Dashboard title
- Report date
- Customer receivables summary
- Supplier payables summary
- Net aging position
- Aging bucket comparison
- Browser print button
- Print-friendly CSS

## Main Files

Controller:

- app/Http/Controllers/ReceivablePayableAgingDashboardController.php

Views:

- resources/views/reports/receivable-payable-aging-dashboard.blade.php
- resources/views/reports/receivable-payable-aging-dashboard-print.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/ReceivablePayableAgingDashboardTest.php
- tests/Feature/ReceivablePayableAgingDashboardExportTest.php
- tests/Feature/ReceivablePayableAgingDashboardPrintTest.php

Related services:

- app/Services/CustomerSalesInvoiceAgingReportBuilder.php
- app/Services/SupplierPurchaseInvoiceAgingReportBuilder.php

## Routes

Main dashboard:

- reports.receivable-payable-aging-dashboard.index

CSV export:

- reports.receivable-payable-aging-dashboard.export

Print view:

- reports.receivable-payable-aging-dashboard.print

## Data Sources

Customer receivables source:

- CustomerSalesInvoiceAgingReportBuilder

Supplier payables source:

- SupplierPurchaseInvoiceAgingReportBuilder

The dashboard reuses approved report builders to keep aging calculations consistent with detailed customer and supplier aging reports.

## Dashboard Summary

Customer summary:

- customers_count
- invoice_count
- remaining_total
- overdue_total

Supplier summary:

- suppliers_count
- invoice_count
- remaining_total
- overdue_total

Net summary:

- net_open_total
- position_label
- net_overdue_total
- overdue_position_label

Bucket comparison:

- label
- customer_total
- supplier_total
- net_total

## Approved Commits

Recent Phase 34 commits:

- 95c9618 Add receivable payable aging dashboard
- 004fd6e Add aging dashboard bucket comparison
- 0e0c780 Add aging dashboard net position cards
- d3469e0 Add aging dashboard CSV export
- 997198b Add aging dashboard print view

## Final Confirmed Test

Last confirmed full test after Phase 34E:

php artisan test = 620 passed / 4491 assertions

## Notes For Future Development

The next logical improvements can be:

- Add dashboard date filter
- Add branch filter
- Add PDF export
- Add drill-down links from each aging bucket
- Add dashboard widgets on the main homepage
- Add scheduled receivable and payable aging summaries
