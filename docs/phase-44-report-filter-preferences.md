# Phase 44 — Report Filter Preferences

## Status

Completed and approved.

## Purpose

Phase 44 added persistent user-specific report filter preferences.

The goal is to allow each authenticated user to keep their last-used report filters per report, then automatically reuse those filters when the user opens the same report again without query parameters.

## Completed Phases

### Phase 44A — Add Report Filter Preference Storage

Added a reusable storage layer for report filter preferences.

Files added or updated:

- database/migrations/2026_07_09_120000_create_user_report_filter_preferences_table.php
- app/Models/UserReportFilterPreference.php
- app/Services/ReportFilterPreferenceService.php
- app/Models/User.php
- tests/Feature/ReportFilterPreferenceServiceTest.php

Storage table:

- user_report_filter_preferences

Columns:

- id
- user_id
- report_key
- filters
- created_at
- updated_at

Important behavior:

- Preferences are scoped by user_id and report_key.
- filters is stored as JSON.
- Empty and null values are ignored.
- Existing preference rows are updated instead of duplicated.
- User has a reportFilterPreferences relation.

Commit:

- 9b4a95e Add report filter preference storage

Confirmed full test:

- php artisan test = 707 passed / 5173 assertions

### Phase 44B — Apply Filter Preferences To Cash Flow Dashboard

Applied report filter preferences to the Cash Flow Dashboard.

Report key:

- cash-flow-dashboard

Saved filters:

- branch_id
- date_from
- date_to

Behavior added:

- When the user opens the dashboard with filters, the filters are saved.
- When the user opens the dashboard without filters, saved filters are reused.
- When reset_filters=1 is used, saved filters are cleared.
- Print and CSV export can reuse saved filters when opened without query parameters.
- Reset link sends reset_filters=1.

Files updated:

- app/Http/Controllers/CashFlowDashboardController.php
- app/Services/ReportFilterPreferenceService.php
- resources/views/reports/cash-flow-dashboard.blade.php

Test added:

- tests/Feature/CashFlowDashboardFilterPreferenceTest.php

Commit:

- 5902f32 Apply filter preferences to cash flow dashboard

Confirmed full test:

- php artisan test = 711 passed / 5196 assertions

### Phase 44C — Apply Filter Preferences To Receivable Payable Aging Dashboard

Applied report filter preferences to the Receivable Payable Aging Dashboard.

Report key:

- receivable-payable-aging-dashboard

Saved filters:

- branch_id
- as_of_date

Behavior added:

- When the user opens the dashboard with filters, the filters are saved.
- When the user opens the dashboard without filters, saved filters are reused.
- When reset_filters=1 is used, saved filters are cleared.
- Print and CSV export can reuse saved filters when opened without query parameters.
- Saved branch preferences are applied to the actual dashboard totals, not only to links.
- Reset link sends reset_filters=1.

Files updated:

- app/Http/Controllers/ReceivablePayableAgingDashboardController.php
- app/Services/ReportFilterPreferenceService.php
- resources/views/reports/receivable-payable-aging-dashboard.blade.php

Test added:

- tests/Feature/ReceivablePayableAgingDashboardFilterPreferenceTest.php

Commit:

- 041a68e Apply filter preferences to receivable payable aging dashboard

Confirmed full test:

- php artisan test = 716 passed / 5225 assertions

## Service API

The preference service is:

- app/Services/ReportFilterPreferenceService.php

Available operations:

- get(User $user, string $reportKey): array
- save(User $user, string $reportKey, array $filters): UserReportFilterPreference
- clear(User $user, string $reportKey): void
- merge(User $user, string $reportKey, array $requestFilters): array

## Current Reports Using Preferences

### Cash Flow Dashboard

Route group:

- reports.cash-flow-dashboard.index
- reports.cash-flow-dashboard.print
- reports.cash-flow-dashboard.export

Report key:

- cash-flow-dashboard

Filters:

- branch_id
- date_from
- date_to

### Receivable Payable Aging Dashboard

Route group:

- reports.receivable-payable-aging-dashboard.index
- reports.receivable-payable-aging-dashboard.print
- reports.receivable-payable-aging-dashboard.export

Report key:

- receivable-payable-aging-dashboard

Filters:

- branch_id
- as_of_date

## Reset Behavior

Reports clear saved preferences when the request contains:

- reset_filters=1

Reset behavior:

- Deletes the saved preference row for the current user and report_key.
- Removes filter values from the current request.
- Returns the report with default filter state.

## Print And Export Behavior

Print and export actions use saved preferences only when opened without explicit filter query parameters.

If explicit filters are passed to print or export, those explicit filters take priority.

Print and export actions do not persist new preferences. Persistence is limited to index/report views where the user actively applies or changes filters.

## Final Confirmed Test

Last confirmed full test after Phase 44C:

- php artisan test = 716 passed / 5225 assertions

## Next Recommended Phase

Phase 45 should extend report preferences to more report pages.

Recommended next phase:

- Phase 45A — Apply Filter Preferences To Customer Aging Drilldown
