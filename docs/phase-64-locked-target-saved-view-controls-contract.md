# Phase 64C — Prepare Locked Target Saved View Controls Contract

## Status

Phase 64C prepared the saved view controls contract for the locked Phase 64 target.

No report implementation files were changed in this phase.

## Locked target

Key:

customer-sales-invoice-aging-drilldown

View path:

resources/views/reports/customer-sales-invoice-aging-drilldown.blade.php

Priority score:

100

## Proposed registry contract

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

Index route:

reports.customer-sales-invoice-aging.index

Export route:

reports.customer-sales-invoice-aging.drilldown.export

Saved view store route:

reports.customer-sales-invoice-aging.drilldown.saved-views.store

## Candidate hidden fields

- aging_bucket
- as_of_date
- branch_id
- customer_id

## Existing route names found in target view

- reports.customer-sales-invoice-aging.drilldown
- reports.customer-sales-invoice-aging.drilldown.export
- reports.customer-sales-invoice-aging.drilldown.saved-views.store
- reports.customer-sales-invoice-aging.index

## Proposed test ids

- section_card: customer-aging-drilldown-saved-views-selector
- form_card: customer-aging-drilldown-save-view-card
- form: customer-aging-drilldown-save-view-form
- name_input: customer-aging-drilldown-saved-view-name-input
- default_checkbox: customer-aging-drilldown-saved-view-default-checkbox
- save_button: customer-aging-drilldown-save-view-button

## Acceptance criteria

Phase 64C is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64A lock and Phase 64B inspection
- the target view exists
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64LockedTargetContractTest

## Next step

Phase 64D should implement the contract by adding the target report to ReportSavedViewRegistry and creating the report-specific config partial.
