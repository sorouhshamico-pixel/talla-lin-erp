# Phase 64A — Select Next Saved View Rollout Target

## Status

Phase 64A selects and locks the next report for saved view rollout after Phase 63.

## Current registered reports

- sales-invoice-aging
- customer-sales-invoice-aging

## Selected target

Key:

customer-sales-invoice-aging-drilldown

View path:

resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php

Priority score:

100

Registered at lock time:

false

## Selection basis

The target was selected from unregistered saved view candidates using this ordering:

1. Highest priority_score
2. Stable key ordering when priorities are equal

## Top unregistered candidates at lock time

1. customer-sales-invoice-aging-drilldown — priority_score: 100 — view: resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php
2. supplier-purchase-invoice-aging — priority_score: 100 — view: resources/views/reports/supplier-purchase-invoice-aging.blade.php
3. supplier-purchase-invoice-aging-drilldown — priority_score: 100 — view: resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
4. cash-flow-dashboard — priority_score: 80 — view: resources/views/reports/cash-flow-dashboard.blade.php
5. index — priority_score: 80 — view: resources/views/reports/index.blade.php
6. profit-loss — priority_score: 80 — view: resources/views/reports/profit-loss.blade.php
7. receivable-payable-aging-dashboard — priority_score: 80 — view: resources/views/reports/receivable-payable-aging-dashboard.blade.php
8. sales-invoice-collection-follow-ups — priority_score: 80 — view: resources/views/reports/sales-invoice-collection-follow-ups.blade.php
9. saved-view-candidates — priority_score: 60 — view: resources/views/reports/saved-view-candidates.blade.php
10. cash-flow-dashboard-print — priority_score: 40 — view: resources/views/reports/cash-flow-dashboard-print.blade.php

## Acceptance criteria

Phase 64A is accepted when:

- the selected target snapshot exists
- the selected target markdown documentation exists
- the target view exists
- the target was unregistered at lock time
- the target can later progress from unregistered to registered without breaking the lock test
- the full test suite passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64NextRolloutTargetTest

## Next step

Phase 64B should inspect the locked target and prepare its report-specific saved view controls config partial.
