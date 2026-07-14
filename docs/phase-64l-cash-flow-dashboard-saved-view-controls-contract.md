# Phase 64L — Prepare Cash Flow Dashboard Saved View Controls Contract

## Status

Phase 64L prepares the saved view controls contract for the Phase 64K locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64K clean
- Commit: 0815aef
- Tests: 1061 passed / 8550 assertions

## Locked target

- Key: cash-flow-dashboard
- View path: resources/views/reports/cash-flow-dashboard.blade.php
- Controller path: app/Http/Controllers/CashFlowDashboardController.php
- Priority score: 80
- Has saved view controls at lock time: no

## Proposed registry and implementation contract

- Registry key: cash-flow-dashboard
- Config partial: reports.partials.cash-flow-dashboard-saved-view-controls-config
- Config partial path: resources/views/reports/partials/cash-flow-dashboard-saved-view-controls-config.blade.php
- View include: @include('reports.partials.cash-flow-dashboard-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.cash-flow-dashboard.index
- Export route: reports.cash-flow-dashboard.export
- Print route: reports.cash-flow-dashboard.print
- Saved view store route to add: reports.cash-flow-dashboard.saved-views.store

## Candidate hidden fields

- branch_id
- date_from
- date_to

## Current state

- Target currently has GET filters: yes
- Target currently has saved view controls: no
- Controller currently uses ReportSavedViewService: no
- Controller currently has storeSavedView method: no
- Routes currently have saved view store route: no

## Existing route names found in target view

- reports.cash-flow-dashboard.export
- reports.cash-flow-dashboard.index
- reports.cash-flow-dashboard.print
- reports.customer-sales-invoice-aging.drilldown
- reports.customer-sales-invoice-aging.index
- reports.index
- reports.receivable-payable-aging-dashboard.index
- reports.supplier-purchase-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.index

## Proposed test ids

- section_card: cash-flow-dashboard-saved-views-selector
- empty: cash-flow-dashboard-saved-views-empty
- form_card: cash-flow-dashboard-save-view-card
- form: cash-flow-dashboard-save-view-form
- name_input: cash-flow-dashboard-saved-view-name-input
- default_checkbox: cash-flow-dashboard-saved-view-default-checkbox
- save_button: cash-flow-dashboard-save-view-button
- list: cash-flow-dashboard-saved-views-list
- item: cash-flow-dashboard-saved-view-item
- open_link: cash-flow-dashboard-saved-view-open-link
- active_badge: cash-flow-dashboard-saved-view-active-badge
- default_badge: cash-flow-dashboard-saved-view-default-badge

## Acceptance criteria

Phase 64L is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64K lock
- the target view exists
- the controller exists and has the expected report key and filter keys
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64LCashFlowDashboardContractTest

## Next step

Phase 64M should implement the contract by adding saved view support to the dashboard controller, route, registry, and report-specific config partial.
