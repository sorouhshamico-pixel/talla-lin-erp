# Phase 64U — Prepare Receivable Payable Aging Dashboard Saved View Controls Contract

## Status

Phase 64U prepares the saved view controls contract for the Phase 64T locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64T clean
- Commit: 21b14af
- Tests: 1103 passed / 9260 assertions

## Locked target

- Key: receivable-payable-aging-dashboard
- View path: resources/views/reports/receivable-payable-aging-dashboard.blade.php
- Controller path: app/Http/Controllers/ReceivablePayableAgingDashboardController.php
- Priority score: 80
- Has saved view controls at lock time: no

## Proposed registry and implementation contract

- Registry key: receivable-payable-aging-dashboard
- Config partial: reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config
- Config partial path: resources/views/reports/partials/receivable-payable-aging-dashboard-saved-view-controls-config.blade.php
- View include: @include('reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.receivable-payable-aging-dashboard.index
- Export route: reports.receivable-payable-aging-dashboard.export
- Print route: reports.receivable-payable-aging-dashboard.print
- Saved view store route to add: reports.receivable-payable-aging-dashboard.saved-views.store

## Candidate hidden fields

- branch_id
- as_of_date

## Current state

- Target currently has GET filters: yes
- Target currently has saved view controls: no
- Controller currently has REPORT_KEY: yes
- Controller currently has FILTER_KEYS: yes
- Controller currently uses ReportFilterPreferenceService: yes
- Controller currently uses ReportSavedViewService: no
- Controller currently has storeSavedView method: no
- Controller currently has index method: yes
- Controller currently has print method: yes
- Controller currently has export method: yes
- Routes currently have saved view store route: no

## Existing route names found in target view

- reports.customer-sales-invoice-aging.drilldown
- reports.customer-sales-invoice-aging.index
- reports.index
- reports.receivable-payable-aging-dashboard.export
- reports.receivable-payable-aging-dashboard.index
- reports.receivable-payable-aging-dashboard.print
- reports.supplier-purchase-invoice-aging.drilldown
- reports.supplier-purchase-invoice-aging.index

## Proposed test ids

- section_card: receivable-payable-aging-dashboard-saved-views-selector
- empty: receivable-payable-aging-dashboard-saved-views-empty
- form_card: receivable-payable-aging-dashboard-save-view-card
- form: receivable-payable-aging-dashboard-save-view-form
- name_input: receivable-payable-aging-dashboard-saved-view-name-input
- default_checkbox: receivable-payable-aging-dashboard-saved-view-default-checkbox
- save_button: receivable-payable-aging-dashboard-save-view-button
- list: receivable-payable-aging-dashboard-saved-views-list
- item: receivable-payable-aging-dashboard-saved-view-item
- open_link: receivable-payable-aging-dashboard-saved-view-open-link
- active_badge: receivable-payable-aging-dashboard-saved-view-active-badge
- default_badge: receivable-payable-aging-dashboard-saved-view-default-badge

## Acceptance criteria

Phase 64U is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64T lock
- the target view exists
- the controller exists
- the current index, export, and print routes exist
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64UReceivablePayableAgingDashboardContractTest

## Next step

Phase 64V should implement the contract by adding saved view support to ReceivablePayableAgingDashboardController, route, registry, and report-specific config partial.
