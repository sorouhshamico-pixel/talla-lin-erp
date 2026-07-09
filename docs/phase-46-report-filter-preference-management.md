# Phase 46 — Report Filter Preference Management

## Status

Completed and approved.

## Purpose

Phase 46 adds a user-facing management page for saved report filter preferences.

The goal is to let each authenticated user review, open, and clear the filters that were saved automatically by the report filter preference system.

## User-Facing Page

Route:

- `reports.filter-preferences.index`

Path:

- `/reports/filter-preferences`

Controller:

- `App\Http\Controllers\ReportFilterPreferenceController`

View:

- `resources/views/reports/filter-preferences/index.blade.php`

The page shows:

- saved report preferences for the current authenticated user
- report display name
- report key
- saved filter labels
- readable saved filter values
- raw filter values as references
- last updated timestamp
- open report link
- delete single report preference action
- delete all saved preferences action

## Management Actions

### View Saved Preferences

Users can view their saved report filter preferences from the report center.

### Delete One Preference

Route:

- `reports.filter-preferences.destroy`

Behavior:

- Deletes one saved preference by `report_key`
- Only deletes preferences owned by the authenticated user

### Delete All Preferences

Route:

- `reports.filter-preferences.destroy-all`

Behavior:

- Deletes all saved report filter preferences for the authenticated user

## Readable Values

Phase 46B added readable value rendering for common saved filter types.

Supported readable mappings:

- `payment_status`
- `aging_bucket`
- `customer_id`
- `supplier_id`
- `branch_id`

Examples:

- `partial` is displayed as `مدفوعة جزئيًا`
- `without_due_date` is displayed as `بدون تاريخ استحقاق`
- customer, supplier, and branch IDs are resolved to names when available

The raw stored value remains visible as a reference.

## Open Report Links

Phase 46C added direct open links for saved preferences.

Each saved report preference can expose an open report URL using the saved filters as query parameters.

Supported report keys:

- `cash-flow-dashboard`
- `receivable-payable-aging-dashboard`
- `customer-sales-invoice-aging`
- `supplier-purchase-invoice-aging`
- `sales-invoice-aging`
- `customer-sales-invoice-aging-drilldown`
- `supplier-purchase-invoice-aging-drilldown`

## Added Tests

- `tests/Feature/ReportFilterPreferenceManagementTest.php`

The tests cover:

- viewing saved preferences
- readable filter labels and values
- opening reports with saved filters
- deleting a single saved preference
- deleting all saved preferences

## Approved Commits

- `06f3b9e Add report filter preference management page`
- `9f36fb1 Fix report filter preference management test`
- `4984e0d Show readable report filter preference values`
- `b6bcd81 Add report links to filter preferences`

## Final Verification

Last confirmed full test run after Phase 46C:

- `php artisan test`
- `739 passed`
- `5318 assertions`
