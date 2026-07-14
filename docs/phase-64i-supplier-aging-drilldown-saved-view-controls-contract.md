# Phase 64I — Prepare Supplier Purchase Invoice Aging Drilldown Saved View Controls Contract

## Status

Phase 64I prepares the saved view controls contract for the Phase 64H locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64H clean
- Commit: c5b4cfe
- Tests: 1047 passed / 8321 assertions

## Locked target

- Key: supplier-purchase-invoice-aging-drilldown
- View path: resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
- Priority score: 100

## Proposed registry contract

- Registry key: supplier-purchase-invoice-aging-drilldown
- Config partial: reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config
- Config partial path: resources/views/reports/partials/supplier-purchase-invoice-aging-drilldown-saved-view-controls-config.blade.php
- View include: @include('reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.supplier-purchase-invoice-aging.drilldown
- Export route: reports.supplier-purchase-invoice-aging.drilldown.export
- Saved view store route: reports.supplier-purchase-invoice-aging.drilldown.saved-views.store

## Candidate hidden fields

- supplier_id
- branch_id
- as_of_date
- aging_bucket

## Existing route names found in target view

- reports.supplier-purchase-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.drilldown.export
- reports.supplier-purchase-invoice-aging.drilldown.saved-views.store
- reports.supplier-purchase-invoice-aging.index

## Proposed test ids

- section_card: supplier-aging-drilldown-saved-views-selector
- empty: supplier-aging-drilldown-saved-views-empty
- form_card: supplier-aging-drilldown-save-view-card
- form: supplier-aging-drilldown-save-view-form
- name_input: supplier-aging-drilldown-saved-view-name-input
- default_checkbox: supplier-aging-drilldown-saved-view-default-checkbox
- save_button: supplier-aging-drilldown-save-view-button
- list: supplier-aging-drilldown-saved-views-list
- item: supplier-aging-drilldown-saved-view-item
- open_link: supplier-aging-drilldown-saved-view-open-link
- active_badge: supplier-aging-drilldown-saved-view-active-badge
- default_badge: supplier-aging-drilldown-saved-view-default-badge

## Acceptance criteria

Phase 64I is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64H lock
- the target view exists
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64ISupplierAgingDrilldownContractTest

## Next step

Phase 64J should implement the contract by adding the target report to ReportSavedViewRegistry and creating the report-specific config partial.
