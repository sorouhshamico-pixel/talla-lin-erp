# Phase 45 — Extended Report Filter Preferences

## Status

Completed and approved.

## Purpose

Phase 45 extends the report filter preference system introduced in Phase 44 to the aging reports and aging drilldown pages.

The objective is to let each authenticated user keep their last-used report filters automatically, so recurring financial review workflows open with the user's preferred context without requiring repeated manual filter selection.

## Implemented Reports

### Customer Sales Invoice Aging Report

Route group:

- `reports.customer-sales-invoice-aging.index`
- `reports.customer-sales-invoice-aging.print`
- `reports.customer-sales-invoice-aging.export`
- `reports.customer-sales-invoice-aging.drilldown`

Report key:

- `customer-sales-invoice-aging`

Saved filters:

- `customer_id`
- `aging_bucket`

Behavior:

- Submitted filters are saved per user.
- Opening the report without filters reuses saved filters.
- `reset_filters=1` clears saved filters.
- Print, export, and drilldown links inherit the active saved filter context.

### Supplier Purchase Invoice Aging Report

Route group:

- `reports.supplier-purchase-invoice-aging.index`
- `reports.supplier-purchase-invoice-aging.print`
- `reports.supplier-purchase-invoice-aging.export`
- `reports.supplier-purchase-invoice-aging.drilldown`

Report key:

- `supplier-purchase-invoice-aging`

Saved filters:

- `supplier_id`
- `aging_bucket`

Behavior:

- Submitted filters are saved per user.
- Opening the report without filters reuses saved filters.
- `reset_filters=1` clears saved filters.
- Print, export, and drilldown links inherit the active saved filter context.

### Sales Invoice Aging Report

Route group:

- `reports.sales-invoice-aging.index`
- `reports.sales-invoice-aging.export`

Report key:

- `sales-invoice-aging`

Saved filters:

- `customer_id`
- `payment_status`
- `aging_bucket`

Behavior:

- Submitted filters are saved per user.
- Opening the report without filters reuses saved filters.
- `reset_filters=1` clears saved filters.
- Export inherits the active saved filter context.

### Customer Sales Invoice Aging Drilldown

Route group:

- `reports.customer-sales-invoice-aging.drilldown`
- `reports.customer-sales-invoice-aging.drilldown.export`

Report key:

- `customer-sales-invoice-aging-drilldown`

Saved filters:

- `customer_id`
- `branch_id`
- `as_of_date`
- `aging_bucket`

Behavior:

- Submitted filters are saved per user.
- Opening the drilldown page without filters reuses saved filters.
- `reset_filters=1` clears saved filters.
- Export inherits the active saved filter context.
- Date presets preserve the active filter context.

### Supplier Purchase Invoice Aging Drilldown

Route group:

- `reports.supplier-purchase-invoice-aging.drilldown`
- `reports.supplier-purchase-invoice-aging.drilldown.export`

Report key:

- `supplier-purchase-invoice-aging-drilldown`

Saved filters:

- `supplier_id`
- `branch_id`
- `as_of_date`
- `aging_bucket`

Behavior:

- Submitted filters are saved per user.
- Opening the drilldown page without filters reuses saved filters.
- `reset_filters=1` clears saved filters.
- Export inherits the active saved filter context.
- Date presets preserve the active filter context.

## Implementation Notes

All Phase 45 reports use:

- `App\Services\ReportFilterPreferenceService`
- `reset_filters=1` to clear saved filters
- per-report `REPORT_KEY`
- explicit filter allowlists
- validation/normalization for date, status, and aging bucket filter values

The implementation avoids persisting unsupported query parameters.

## Added Tests

- `tests/Feature/CustomerSalesInvoiceAgingReportFilterPreferenceTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingReportFilterPreferenceTest.php`
- `tests/Feature/SalesInvoiceAgingReportFilterPreferenceTest.php`
- `tests/Feature/CustomerSalesInvoiceAgingDrilldownFilterPreferenceTest.php`
- `tests/Feature/SupplierPurchaseInvoiceAgingDrilldownFilterPreferenceTest.php`

## Final Verification

Last confirmed full test run after Phase 45E:

- `php artisan test`
- `736 passed`
- `5300 assertions`

## Approved Commits

- `5ef5057 Apply filter preferences to customer aging report`
- `2f846af Apply filter preferences to supplier aging report`
- `c2f91a0 Apply filter preferences to sales invoice aging report`
- `606a71d Apply filter preferences to customer aging drilldown`
- `e478d7f Apply filter preferences to supplier aging drilldown`
