# Phase 64O — Prepare Reports Index Saved View Controls Contract

## Status

Phase 64O prepares the saved view controls contract for the Phase 64N locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64N clean
- Commit: f5032ad
- Tests: 1075 passed / 8780 assertions

## Locked target

- Key: index
- View path: resources/views/reports/index.blade.php
- Controller path: app/Http/Controllers/ReportController.php
- Priority score: 80
- Has saved view controls at lock time: no

## Proposed registry and implementation contract

- Registry key: index
- Config partial: reports.partials.index-saved-view-controls-config
- Config partial path: resources/views/reports/partials/index-saved-view-controls-config.blade.php
- View include: @include('reports.partials.index-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.index
- Saved view store route to add: reports.index.saved-views.store

## Candidate hidden fields

- from_date
- to_date
- branch_id
- expense_category_id
- payment_method

## Current state

- Target currently has GET filters: yes
- Target currently has saved view controls: no
- Controller currently has REPORT_KEY: no
- Controller currently uses ReportSavedViewService: no
- Controller currently has storeSavedView method: no
- Routes currently have saved view store route: no

## Existing route names found in target view

- reports.cash-flow-dashboard.index
- reports.customer-sales-invoice-aging.index
- reports.filter-preferences.index
- reports.index
- reports.receivable-payable-aging-dashboard.index
- reports.sales-invoice-aging.index
- reports.sales-invoice-collection-follow-ups.index
- reports.sales-invoice-collections.index
- reports.saved-view-diagnostics.index
- reports.saved-views.index
- reports.supplier-purchase-invoice-aging.index

## Proposed test ids

- section_card: reports-index-saved-views-selector
- empty: reports-index-saved-views-empty
- form_card: reports-index-save-view-card
- form: reports-index-save-view-form
- name_input: reports-index-saved-view-name-input
- default_checkbox: reports-index-saved-view-default-checkbox
- save_button: reports-index-save-view-button
- list: reports-index-saved-views-list
- item: reports-index-saved-view-item
- open_link: reports-index-saved-view-open-link
- active_badge: reports-index-saved-view-active-badge
- default_badge: reports-index-saved-view-default-badge

## Acceptance criteria

Phase 64O is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64N lock
- the target view exists
- the controller exists
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64OReportsIndexContractTest

## Next step

Phase 64P should implement the contract by adding saved view support to ReportController, route, registry, and report-specific config partial.
