# Phase 63B — Inspect Locked Report Saved View Rollout Target

## Status

Phase 63B inspects the locked report saved view rollout target before modifying the report.

## Locked target

Key:

customer-sales-invoice-aging

View path:

resources/views/reports/customer-sales-invoice-aging.blade.php

Priority score:

100

## View inspection

Line count:

224

Form count:

2

GET form count:

1

Already has saved view controls:

no

Has detected filters:

yes

## Candidate filter fields

- customer_id
- aging_bucket
- name
- is_default

## Route names detected in Blade

- reports.index
- reports.customer-sales-invoice-aging.print
- reports.customer-sales-invoice-aging.drilldown
- reports.customer-sales-invoice-aging.export
- reports.sales-invoice-aging.index
- reports.customer-sales-invoice-aging.index
- reports.customer-sales-invoice-aging.saved-views.store
- sales-invoices.index

## Includes detected in Blade

- reports.partials.saved-view-section

## Recommended config partial

Blade include name:

reports.partials.customer-sales-invoice-aging-saved-view-controls-config

File path:

resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php

## Recommended registry key

customer-sales-invoice-aging

## Snapshot files

- docs/phase-63-report-saved-view-rollout-target-inspection.md
- docs/phase-63-report-saved-view-rollout-target-inspection.json

## Next implementation phase

Phase 63C should use this inspection to add the actual saved view controls config partial and registry metadata for:

customer-sales-invoice-aging

## Guard test

This phase is protected by:

ReportSavedViewRolloutTargetInspectionTest
