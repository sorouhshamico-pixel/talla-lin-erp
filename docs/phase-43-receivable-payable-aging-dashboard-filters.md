# Phase 43 — Receivable Payable Aging Dashboard Filters

## Status

Completed and approved.

## Purpose

Phase 43 improved the Receivable Payable Aging Dashboard by adding operational filters, preserving filter context across dashboard actions, and adding quick report date presets.

The dashboard can now be analyzed by branch and report date, with the same context preserved in print, CSV export, and aging drilldown/report links.

## Completed Phases

### Phase 43A — Add Branch And Report Date Filters To Receivable Payable Aging Dashboard

Added filters to the Receivable Payable Aging Dashboard.

Filters added:

- branch_id
- as_of_date

Main behavior:

- Dashboard displays a branch selector.
- Dashboard displays a report date input.
- Report date changes the aging classification.
- Branch filter limits customer receivables and supplier payables to the selected branch.
- Print, export, customer aging report, supplier aging report, and drilldown links preserve filter parameters.

Important implementation note:

- Commit 607eb38 added the dashboard filters.
- Commit dbe099c fixed the actual filter application inside the service builders.

Files updated:

- app/Http/Controllers/ReceivablePayableAgingDashboardController.php
- resources/views/reports/receivable-payable-aging-dashboard.blade.php
- app/Services/CustomerSalesInvoiceAgingReportBuilder.php
- app/Services/SupplierPurchaseInvoiceAgingReportBuilder.php

Test added:

- tests/Feature/ReceivablePayableAgingDashboardFilterTest.php

Commits:

- 607eb38 Add filters to receivable payable aging dashboard
- dbe099c Fix receivable payable aging dashboard filters

Confirmed full test after fix:

- php artisan test = 698 passed / 5135 assertions

### Phase 43B — Add Filter Context To Receivable Payable Aging Print And Export

Added filter context to print and CSV export.

Context added:

- Branch
- Selected report date

Main behavior:

- Print view displays the selected branch and report date.
- CSV export includes the selected branch and report date.
- Print and export data use the same filters as the dashboard.

Files updated:

- app/Http/Controllers/ReceivablePayableAgingDashboardController.php
- resources/views/reports/receivable-payable-aging-dashboard-print.blade.php

Test added:

- tests/Feature/ReceivablePayableAgingDashboardFilterContextTest.php

Commit:

- 5771bc5 Add filter context to receivable payable aging print and export

Confirmed full test:

- php artisan test = 700 passed / 5146 assertions

### Phase 43C — Add Report Date Presets To Receivable Payable Aging Dashboard

Added quick report date preset links.

Presets added:

- Today
- Current month end
- Next month end
- Current quarter end

Arabic labels used:

- اليوم
- نهاية الشهر الحالي
- نهاية الشهر القادم
- نهاية الربع الحالي

Main behavior:

- Preset links set as_of_date automatically.
- Preset links preserve branch_id when a branch is selected.
- Reset link clears all filters.

Files updated:

- resources/views/reports/receivable-payable-aging-dashboard.blade.php

Test added:

- tests/Feature/ReceivablePayableAgingDashboardReportDatePresetTest.php

Commit:

- 1db023a Add report date presets to receivable payable aging dashboard

Confirmed full test:

- php artisan test = 702 passed / 5161 assertions

## Current Dashboard Capabilities

The Receivable Payable Aging Dashboard now supports:

- Branch filtering.
- Report date filtering.
- Filtered customer receivable aging totals.
- Filtered supplier payable aging totals.
- Filtered net open aging position.
- Filtered net overdue position.
- Filtered bucket comparison.
- Filter-aware customer aging drilldown links.
- Filter-aware supplier aging drilldown links.
- Filter-aware print link.
- Filter-aware CSV export link.
- Filter-aware customer aging report link.
- Filter-aware supplier aging report link.
- Filter context in print view.
- Filter context in CSV export.
- Quick report date presets.

## Date Logic

The selected as_of_date controls:

- Report date shown on the dashboard.
- Aging bucket classification.
- Print view report date.
- CSV export report date.
- Drilldown aging classification when the parameter is preserved.

When as_of_date is not provided:

- The current system date is used.

## Branch Filter Logic

The selected branch_id is applied inside:

- CustomerSalesInvoiceAgingReportBuilder
- SupplierPurchaseInvoiceAgingReportBuilder

This ensures the dashboard summaries and bucket comparison are calculated from filtered source data, not only from filtered links.

## Files Added Or Updated

Controllers:

- app/Http/Controllers/ReceivablePayableAgingDashboardController.php

Services:

- app/Services/CustomerSalesInvoiceAgingReportBuilder.php
- app/Services/SupplierPurchaseInvoiceAgingReportBuilder.php

Views:

- resources/views/reports/receivable-payable-aging-dashboard.blade.php
- resources/views/reports/receivable-payable-aging-dashboard-print.blade.php

Tests:

- tests/Feature/ReceivablePayableAgingDashboardFilterTest.php
- tests/Feature/ReceivablePayableAgingDashboardFilterContextTest.php
- tests/Feature/ReceivablePayableAgingDashboardReportDatePresetTest.php

Documentation:

- docs/phase-43-receivable-payable-aging-dashboard-filters.md

## Final Confirmed Test

Last confirmed full test after Phase 43C:

- php artisan test = 702 passed / 5161 assertions

## Next Recommended Phase

Phase 44 should focus on dashboard filter preferences.

Recommended next phase:

- Phase 44A — Persist Report Filter Preferences
