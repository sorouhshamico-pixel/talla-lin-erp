# Phase 33 — Supplier Purchase Invoice Aging Report

## Status

Completed and approved.

## Purpose

Phase 33 added a supplier-level aging report for open purchase invoice payables. The report helps track outstanding supplier balances by aging buckets and supports filtering, CSV export, print view, and reusable report-building logic.

## Completed Phases

### Phase 33A — Supplier Purchase Invoice Aging Report Skeleton

Main output:

- Supplier purchase invoice aging report controller
- Supplier aging report route
- Initial report view
- Link from reports center
- Initial feature test

Files added:

- app/Http/Controllers/SupplierPurchaseInvoiceAgingReportController.php
- resources/views/reports/supplier-purchase-invoice-aging.blade.php
- tests/Feature/SupplierPurchaseInvoiceAgingReportTest.php

Route:

- reports.supplier-purchase-invoice-aging.index

### Phase 33B — Build Supplier Purchase Invoice Aging Summary

Added report data builder and connected the report to real purchase invoice data.

Service:

- app/Services/SupplierPurchaseInvoiceAgingReportBuilder.php

Report includes:

- Supplier-level grouping
- Open purchase invoices only
- Aging bucket totals
- Oldest due date
- General summary
- Empty state

Open invoice condition:

- remaining_amount > 0

Supported filters:

- supplier_id
- aging_bucket

Supported aging buckets:

- not_due
- overdue_1_30
- overdue_31_60
- overdue_61_90
- overdue_more_than_90
- without_due_date

### Phase 33C — Add Supplier Aging Report Filters

Added filter controls to the report page.

Filters:

- Supplier select
- Aging bucket select
- Apply filters button
- Reset filters link

Test added:

- tests/Feature/SupplierPurchaseInvoiceAgingReportFiltersTest.php

Important testing note:

- Supplier names can appear inside the filter select even when the table results are filtered out.
- Tests should not use assertDontSee for supplier names that can appear inside filter options.
- Filtering should be verified through amounts, table rows, or more specific assertions.

### Phase 33D — Export Supplier Purchase Invoice Aging Report

Added CSV export for the supplier purchase invoice aging report.

Route:

- reports.supplier-purchase-invoice-aging.export

Controller method:

- SupplierPurchaseInvoiceAgingReportController::export

View update:

- Added CSV export button to resources/views/reports/supplier-purchase-invoice-aging.blade.php

Test added:

- tests/Feature/SupplierPurchaseInvoiceAgingReportExportTest.php

CSV includes:

- Report title
- Export generation date
- Report date
- Supplier filter label
- Aging bucket filter label
- General summary
- Supplier count
- Open invoice count
- Total open payables
- Total overdue
- Supplier aging table

CSV supplier aging table columns:

- المورد
- عدد الفواتير
- إجمالي المتبقي
- غير مستحقة بعد
- متأخرة 1 إلى 30
- متأخرة 31 إلى 60
- متأخرة 61 إلى 90
- أكثر من 90
- بدون تاريخ استحقاق
- أقدم استحقاق

### Phase 33E — Supplier Purchase Invoice Aging Report Print View

Added printable view for the supplier purchase invoice aging report.

Route:

- reports.supplier-purchase-invoice-aging.print

Controller method:

- SupplierPurchaseInvoiceAgingReportController::print

Views:

- resources/views/reports/supplier-purchase-invoice-aging-print.blade.php
- Print link added to resources/views/reports/supplier-purchase-invoice-aging.blade.php

Test added:

- tests/Feature/SupplierPurchaseInvoiceAgingReportPrintTest.php

Print view includes:

- Report title
- Report date
- Supplier filter label
- Aging bucket filter label
- Summary cards
- Supplier aging table
- Empty state
- Browser print button
- Print-friendly CSS

## Main Files

Controller:

- app/Http/Controllers/SupplierPurchaseInvoiceAgingReportController.php

Service:

- app/Services/SupplierPurchaseInvoiceAgingReportBuilder.php

Views:

- resources/views/reports/supplier-purchase-invoice-aging.blade.php
- resources/views/reports/supplier-purchase-invoice-aging-print.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/SupplierPurchaseInvoiceAgingReportTest.php
- tests/Feature/SupplierPurchaseInvoiceAgingReportFiltersTest.php
- tests/Feature/SupplierPurchaseInvoiceAgingReportExportTest.php
- tests/Feature/SupplierPurchaseInvoiceAgingReportPrintTest.php

## Routes

Main report:

- reports.supplier-purchase-invoice-aging.index

CSV export:

- reports.supplier-purchase-invoice-aging.export

Print view:

- reports.supplier-purchase-invoice-aging.print

## Filtering Rules

The report supports the following request filters:

- supplier_id
- aging_bucket

## Report Calculations

The report only includes purchase invoices where:

- remaining_amount > 0

Calculated per supplier:

- invoice_count
- remaining_total
- not_due_total
- overdue_1_30_total
- overdue_31_60_total
- overdue_61_90_total
- overdue_more_than_90_total
- without_due_date_total
- oldest_due_at

General summary:

- suppliers_count
- invoice_count
- remaining_total
- overdue_total

## Implementation Notes

Purchase invoice tests must include warehouse_id because purchase_invoices.warehouse_id is required.

Purchase invoice tests should use a valid purchase invoice status from the existing migration or schema constraints. Hardcoding an invalid status can fail SQLite check constraints during tests.

When validating filtered supplier results in pages that include a supplier select box, do not assert that an excluded supplier name is absent from the full HTML response. The supplier may still appear as an option in the filter list.

## Approved Commits

Recent Phase 33 commits:

- c30c71c Add supplier purchase invoice aging report skeleton
- a172922 Build supplier purchase invoice aging report
- 9a17486 Add supplier aging report filters
- 90d170d Add supplier aging report export
- abb2f23 Add supplier aging report print view

## Final Confirmed Test

Last confirmed full test after Phase 33E:

php artisan test = 612 passed / 4422 assertions

## Notes For Future Development

The next logical improvements can be:

- Add PDF export
- Add supplier aging dashboard card
- Add scheduled supplier aging report
- Add direct email sending to management
- Add drill-down page from supplier row to open purchase invoices
