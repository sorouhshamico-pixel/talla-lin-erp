# Phase 65E — Exclude Print-Only Saved View Candidates And Lock Next Target

## Baseline

- Previous phase: Phase 65D clean
- Commit: 7817b74
- Tests: 1150 passed / 10020 assertions

## Selector change

- Print-only candidates are excluded from `ReportSavedViewRolloutSelector::prioritizedCandidates()`.
- Exclusion rule: candidate key ending with `-print` or view path ending with `-print.blade.php`.
- Excluded print-only candidate count: 4

## Excluded print-only candidates

- cash-flow-dashboard-print — resources/views/reports/cash-flow-dashboard-print.blade.php
- customer-sales-invoice-aging-print — resources/views/reports/customer-sales-invoice-aging-print.blade.php
- receivable-payable-aging-dashboard-print — resources/views/reports/receivable-payable-aging-dashboard-print.blade.php
- supplier-purchase-invoice-aging-print — resources/views/reports/supplier-purchase-invoice-aging-print.blade.php

## Locked next non-print target

- Key: sales-invoice-collections
- View path: resources/views/reports/sales-invoice-collections.blade.php
- Priority score: 40
- Registered at lock time: no
- Has GET form: no
- Has filters: yes
- Has saved view controls: no

## Proposed contract seed

- Registry key: sales-invoice-collections
- Config partial: reports.partials.sales-invoice-collections-saved-view-controls-config
- Config partial path: resources/views/reports/partials/sales-invoice-collections-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Next step

Phase 65F should inspect the locked non-print target and prepare a focused saved view controls contract.
