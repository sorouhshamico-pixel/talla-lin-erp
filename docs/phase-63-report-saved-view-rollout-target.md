# Phase 63A — Lock Next Report Saved View Rollout Target

## Status

Phase 63A locks the next report saved view rollout target selected by the rollout selector.

## Selected target

Key:

customer-sales-invoice-aging

View path:

resources/views/reports/customer-sales-invoice-aging.blade.php

Priority score:

100

Has GET form:

yes

Has filters:

yes

Already has saved view controls:

yes

## Selector source

The target was selected from:

php artisan reports:saved-view-rollout-selector --json

## Snapshot files

- docs/phase-63-report-saved-view-rollout-target.md
- docs/phase-63-report-saved-view-rollout-target.json

## Next implementation phase

Phase 63B should inspect and modify the selected Blade report view:

resources/views/reports/customer-sales-invoice-aging.blade.php

The implementation should add report-specific saved view controls, registry metadata, and render guards for:

customer-sales-invoice-aging

## Guard test

This phase is protected by:

ReportSavedViewRolloutTargetLockTest
