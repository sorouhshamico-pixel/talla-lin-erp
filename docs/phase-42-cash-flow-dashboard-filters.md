# Phase 42 — Cash Flow Dashboard Filters

## Status

Completed and approved.

## Purpose

Phase 42 improved the cash flow dashboard by adding practical filtering, filter context, and quick date range presets.

The cash flow dashboard can now be analyzed by branch and due date range, with the same context preserved in print and CSV export outputs.

## Completed Phases

### Phase 42A — Add Date Range Filter To Cash Flow Dashboard

Added filtering to the cash flow dashboard.

Filters added:

- branch_id
- date_from
- date_to

Main behavior:

- Filters expected inflows from open sales invoices.
- Filters expected outflows from open purchase invoices.
- Uses due_at as the date basis.
- Uses date_to as the report date when provided.
- Keeps today as the report date when date_to is not provided.
- Preserves filters in dashboard print and export links.
- Preserves branch_id and as_of_date when navigating from cash flow bucket rows to aging drilldown reports.

Files updated:

- app/Http/Controllers/CashFlowDashboardController.php
- resources/views/reports/cash-flow-dashboard.blade.php

Test added:

- tests/Feature/CashFlowDashboardDateRangeFilterTest.php

Commit:

- ecf2345 Add date range filter to cash flow dashboard

Confirmed full test:

- php artisan test = 691 passed / 5072 assertions

### Phase 42B — Add Filter Context To Cash Flow Print And Export

Added filter context to the cash flow dashboard print view and CSV export.

Context added:

- Branch
- Due date from
- Due date to

Main behavior:

- Print view displays the selected branch and due date range.
- CSV export includes the selected branch and due date range.
- Print and export totals respect the same filters used in the dashboard.

Files updated:

- app/Http/Controllers/CashFlowDashboardController.php
- resources/views/reports/cash-flow-dashboard-print.blade.php

Test added:

- tests/Feature/CashFlowDashboardFilterContextTest.php

Commit:

- 145db98 Add filter context to cash flow print and export

Confirmed full test:

- php artisan test = 693 passed / 5094 assertions

### Phase 42C — Add Date Range Presets To Cash Flow Dashboard

Added quick date range preset links to the cash flow dashboard.

Presets added:

- Current month
- Next 30 days
- Next month
- Until today

Arabic labels used:

- الشهر الحالي
- الثلاثون يومًا القادمة
- الشهر القادم
- حتى اليوم

Main behavior:

- Preset links set date_from and date_to automatically.
- Preset links preserve branch_id when a branch is selected.
- Reset link clears all filters.

Files updated:

- resources/views/reports/cash-flow-dashboard.blade.php

Test added:

- tests/Feature/CashFlowDashboardDateRangePresetTest.php

Commit:

- 3d47471 Add date range presets to cash flow dashboard

Confirmed full test:

- php artisan test = 695 passed / 5109 assertions

## Current Cash Flow Dashboard Capabilities

The dashboard now supports:

- Branch filtering.
- Due date range filtering.
- Filtered inflow totals.
- Filtered outflow totals.
- Filtered net expected cash flow.
- Filtered overdue cash flow risk.
- Filtered bucket comparison.
- Filter context in print view.
- Filter context in CSV export.
- Date range presets.
- Filter-aware print and export links.
- Filter-aware aging drilldown links using branch_id and as_of_date.

## Date Logic

When date_to is provided:

- reportDate uses date_to.

When date_to is not provided:

- reportDate uses the current system date.

date_from and date_to filter open invoices using due_at.

Invoices without due_at are excluded when a due date filter is applied.

## Files Added Or Updated

Controllers:

- app/Http/Controllers/CashFlowDashboardController.php

Views:

- resources/views/reports/cash-flow-dashboard.blade.php
- resources/views/reports/cash-flow-dashboard-print.blade.php

Tests:

- tests/Feature/CashFlowDashboardDateRangeFilterTest.php
- tests/Feature/CashFlowDashboardFilterContextTest.php
- tests/Feature/CashFlowDashboardDateRangePresetTest.php

Documentation:

- docs/phase-42-cash-flow-dashboard-filters.md

## Final Confirmed Test

Last confirmed full test after Phase 42C:

- php artisan test = 695 passed / 5109 assertions

## Next Recommended Phase

Phase 43 should focus on the Receivable Payable Aging Dashboard.

Recommended next phase:

- Phase 43A — Add Branch And Report Date Filters To Receivable Payable Aging Dashboard
