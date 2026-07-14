# Phase 64F — Prepare Supplier Purchase Invoice Aging Saved View Controls Contract

## Status

Phase 64F prepares the saved view controls contract for the Phase 64E locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64E clean
- Commit: acee66e
- Tests: 1033 passed / 8098 assertions

## Locked target

- Key: supplier-purchase-invoice-aging
- View path: resources/views/reports/supplier-purchase-invoice-aging.blade.php
- Priority score: 100

## Proposed registry contract

- Registry key: supplier-purchase-invoice-aging
- Config partial: reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config
- Config partial path: resources/views/reports/partials/supplier-purchase-invoice-aging-saved-view-controls-config.blade.php
- View include: @include('reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.supplier-purchase-invoice-aging.index
- Export route: reports.supplier-purchase-invoice-aging.export
- Saved view store route: reports.supplier-purchase-invoice-aging.saved-views.store

## Candidate hidden fields

- supplier_id
- aging_bucket

## Existing route names found in target view

- reports.index
- reports.supplier-purchase-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.export
- reports.supplier-purchase-invoice-aging.index
- reports.supplier-purchase-invoice-aging.print
- reports.supplier-purchase-invoice-aging.saved-views.store

## Proposed test ids

- section_card: supplier-aging-saved-views-selector
- empty: supplier-aging-saved-views-empty
- form_card: supplier-aging-save-view-card
- form: supplier-aging-save-view-form
- name_input: supplier-aging-saved-view-name-input
- default_checkbox: supplier-aging-saved-view-default-checkbox
- save_button: supplier-aging-save-view-button
- list: supplier-aging-saved-views-list
- item: supplier-aging-saved-view-item
- open_link: supplier-aging-saved-view-open-link
- active_badge: supplier-aging-saved-view-active-badge
- default_badge: supplier-aging-saved-view-default-badge

## Acceptance criteria

Phase 64F is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64E lock
- the target view exists
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64FSupplierAgingContractTest

## Next step

Phase 64G should implement the contract by adding the target report to ReportSavedViewRegistry and creating the report-specific config partial.
