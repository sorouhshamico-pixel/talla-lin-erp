# Phase 64B — Inspect Locked Saved View Rollout Target

## Status

Phase 64B inspected the locked saved view rollout target selected in Phase 64A.

## Locked target

Key:

customer-sales-invoice-aging-drilldown

View path:

resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php

Priority score:

100

Registered at lock time:

false

## View inspection

View exists:

true

Line count:

209

Contains GET form:

true

Contains filter input names:

true

Already contains shared saved view controls:

false

Already contains report-specific config partial:

false

## Blade includes found

- reports.partials.saved-view-section

## Route names found

- reports.customer-sales-invoice-aging.drilldown
- reports.customer-sales-invoice-aging.drilldown.export
- reports.customer-sales-invoice-aging.drilldown.saved-views.store
- reports.customer-sales-invoice-aging.index

## Input names found

- aging_bucket
- as_of_date
- branch_id
- customer_id
- is_default
- name

## Data test ids found

- customer-aging-drilldown-apply-filters
- customer-aging-drilldown-as-of-date-filter
- customer-aging-drilldown-as-of-date-input
- customer-aging-drilldown-branch-filter
- customer-aging-drilldown-branch-select
- customer-aging-drilldown-bucket-filter
- customer-aging-drilldown-bucket-select
- customer-aging-drilldown-customer-filter
- customer-aging-drilldown-customer-select
- customer-aging-drilldown-date-preset-month-end
- customer-aging-drilldown-date-preset-previous-month-end
- customer-aging-drilldown-date-preset-quarter-end
- customer-aging-drilldown-date-preset-today
- customer-aging-drilldown-date-presets
- customer-aging-drilldown-empty
- customer-aging-drilldown-export-link
- customer-aging-drilldown-filters
- customer-aging-drilldown-report-date
- customer-aging-drilldown-reset-filters
- customer-aging-drilldown-save-view-button
- customer-aging-drilldown-save-view-card
- customer-aging-drilldown-save-view-form
- customer-aging-drilldown-saved-view-default-checkbox
- customer-aging-drilldown-saved-view-name-input
- customer-aging-drilldown-saved-views-selector
- customer-aging-drilldown-status
- customer-aging-drilldown-summary
- customer-aging-drilldown-table
- customer-sales-invoice-aging-drilldown

## Recommended rollout contract

Registry key:

customer-sales-invoice-aging-drilldown

Config partial:

reports.partials.customer-sales-invoice-aging-drilldown-saved-view-controls-config

Config partial path:

resources/views/reports/partials/customer-sales-invoice-aging-drilldown-saved-view-controls-config.blade.php

View include:

@include('reports.partials.customer-sales-invoice-aging-drilldown-saved-view-controls-config')

Shared controls partial:

reports.partials.saved-view-controls

## Acceptance criteria

Phase 64B is accepted when:

- the inspection JSON exists
- the inspection markdown exists
- the inspected target matches the Phase 64A lock
- the target view exists
- the recommended config partial path is documented
- the full test suite passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64LockedTargetInspectionTest

## Next step

Phase 64C should prepare the report-specific saved view controls contract for this locked target.
