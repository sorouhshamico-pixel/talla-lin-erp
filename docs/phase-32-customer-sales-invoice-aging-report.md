# Phase 32 — Customer Sales Invoice Aging Report

## Status

Completed and approved.

## Purpose

Phase 32 added a customer-level aging report for open sales invoice receivables. The report helps track outstanding customer balances by aging buckets and supports filtering, CSV export, print view, and reuse of report-building logic.

## Completed Phases

### Phase 32A — Add customer sales invoice aging summary report

Main output:

- Customer-level aging report page
- Grouping open sales invoices by customer
- Aging bucket totals
- Summary totals
- Link from reports center

### Phase 32B — Add filters to customer sales invoice aging report

Supported filters:

- customer_id
- aging_bucket

Supported aging buckets:

- not_due
- overdue_1_30
- overdue_31_60
- overdue_61_90
- overdue_more_than_90
- without_due_date

### Phase 32C — Export Customer Sales Invoice Aging Report

Added CSV export for the customer sales invoice aging report.

Route:

- reports.customer-sales-invoice-aging.export

Controller method:

- CustomerSalesInvoiceAgingReportController::export

View update:

- Added CSV export button to resources/views/reports/customer-sales-invoice-aging.blade.php

Test added:

- tests/Feature/CustomerSalesInvoiceAgingReportExportTest.php

CSV includes:

- Report title
- Export generation date
- Report date
- Customer filter label
- Aging bucket filter label
- General summary
- Customer count
- Open invoice count
- Total open receivables
- Total overdue
- Customer aging table

CSV customer aging table columns:

- العميل
- عدد الفواتير
- إجمالي المتبقي
- غير مستحقة بعد
- متأخرة 1 إلى 30
- متأخرة 31 إلى 60
- متأخرة 61 إلى 90
- أكثر من 90
- بدون تاريخ استحقاق
- أقدم استحقاق

### Phase 32D — Customer Sales Invoice Aging Report Print View

Added printable view for the customer sales invoice aging report.

Route:

- reports.customer-sales-invoice-aging.print

Controller method:

- CustomerSalesInvoiceAgingReportController::print

Views:

- resources/views/reports/customer-sales-invoice-aging-print.blade.php
- Print link added to resources/views/reports/customer-sales-invoice-aging.blade.php

Test added:

- tests/Feature/CustomerSalesInvoiceAgingReportPrintTest.php

Print view includes:

- Report title
- Report date
- Customer filter label
- Aging bucket filter label
- Summary cards
- Customer aging table
- Empty state
- Browser print button
- Print-friendly CSS

### Phase 32E — Refactor Customer Sales Invoice Aging Report Data Builder

Moved shared report-building logic into a dedicated service.

Service:

- app/Services/CustomerSalesInvoiceAgingReportBuilder.php

Used by:

- CustomerSalesInvoiceAgingReportController::export
- CustomerSalesInvoiceAgingReportController::print

Purpose:

- Reduce duplicated report logic
- Keep export and print behavior consistent
- Prepare the report for future enhancements

## Main Files

Controller:

- app/Http/Controllers/CustomerSalesInvoiceAgingReportController.php

Service:

- app/Services/CustomerSalesInvoiceAgingReportBuilder.php

Views:

- resources/views/reports/customer-sales-invoice-aging.blade.php
- resources/views/reports/customer-sales-invoice-aging-print.blade.php

Routes:

- routes/web.php

Tests:

- tests/Feature/CustomerSalesInvoiceAgingReportTest.php
- tests/Feature/CustomerSalesInvoiceAgingReportExportTest.php
- tests/Feature/CustomerSalesInvoiceAgingReportPrintTest.php

## Routes

Main report:

- reports.customer-sales-invoice-aging.index

CSV export:

- reports.customer-sales-invoice-aging.export

Print view:

- reports.customer-sales-invoice-aging.print

## Filtering Rules

The report supports the following request filters:

- customer_id
- aging_bucket

## Report Calculations

The report only includes sales invoices where:

- remaining_amount > 0

Calculated per customer:

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

- customers_count
- invoice_count
- remaining_total
- overdue_total

## Approved Commits

Recent Phase 32 commits:

- b2477ce Add customer sales invoice aging summary report
- 321c3ad Add filters to customer sales invoice aging report
- f38c13f Add customer aging export route
- ab3cae3 Build customer aging export data
- 264741f Add customer aging export summary
- 92a6f58 Add customer aging export table
- 91f70aa Add customer aging export button
- 1eeea10 Add customer aging export feature test
- ebe7cd1 Add customer aging print route
- b455f75 Complete customer aging print view
- 66b1ad2 Refactor customer aging export and print data builder

## Final Confirmed Test

Last confirmed full test after Phase 32E:

php artisan test = 599 passed / 4315 assertions

## Notes For Future Development

The next logical improvements can be:

- Add PDF export
- Add direct email sending of the report
- Add saved report filters
- Add scheduled aging report generation
- Add dashboard card linking to overdue customer receivables
