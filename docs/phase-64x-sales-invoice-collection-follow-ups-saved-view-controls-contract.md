# Phase 64X — Prepare Sales Invoice Collection Follow Ups Saved View Controls Contract

## Status

Phase 64X prepares the saved view controls contract for the Phase 64W locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64W clean
- Commit: ebc6079
- Tests: 1117 passed / 9509 assertions

## Locked target

- Key: sales-invoice-collection-follow-ups
- View path: resources/views/reports/sales-invoice-collection-follow-ups.blade.php
- Controller path: app/Http/Controllers/SalesInvoiceCollectionFollowUpReportController.php
- Priority score: 80
- Has saved view controls at lock time: no

## Proposed registry and implementation contract

- Registry key: sales-invoice-collection-follow-ups
- Config partial: reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config
- Config partial path: resources/views/reports/partials/sales-invoice-collection-follow-ups-saved-view-controls-config.blade.php
- View include: @include('reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.sales-invoice-collection-follow-ups.index
- Export route: reports.sales-invoice-collection-follow-ups.export
- Saved view store route to add: reports.sales-invoice-collection-follow-ups.saved-views.store

## Candidate hidden fields

- customer_id
- follow_up_from
- follow_up_to

## Current state

- Target currently has GET filters: yes
- Target currently has saved view controls: no
- Controller currently has REPORT_KEY: no
- Controller currently uses ReportSavedViewService: no
- Controller currently has storeSavedView method: no
- Controller currently has index method: yes
- Controller currently has export method: yes
- Routes currently have saved view store route: no

## Existing route names found in target view

- reports.index
- reports.sales-invoice-collection-follow-ups.export
- reports.sales-invoice-collection-follow-ups.index
- reports.sales-invoice-collections.index
- sales-invoices.show

## Proposed test ids

- section_card: sales-invoice-collection-follow-ups-saved-views-selector
- empty: sales-invoice-collection-follow-ups-saved-views-empty
- form_card: sales-invoice-collection-follow-ups-save-view-card
- form: sales-invoice-collection-follow-ups-save-view-form
- name_input: sales-invoice-collection-follow-ups-saved-view-name-input
- default_checkbox: sales-invoice-collection-follow-ups-saved-view-default-checkbox
- save_button: sales-invoice-collection-follow-ups-save-view-button
- list: sales-invoice-collection-follow-ups-saved-views-list
- item: sales-invoice-collection-follow-ups-saved-view-item
- open_link: sales-invoice-collection-follow-ups-saved-view-open-link
- active_badge: sales-invoice-collection-follow-ups-saved-view-active-badge
- default_badge: sales-invoice-collection-follow-ups-saved-view-default-badge

## Acceptance criteria

Phase 64X is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64W lock
- the target view exists
- the controller exists
- the current index and export routes exist
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64XSalesInvoiceCollectionFollowUpsContractTest

## Next step

Phase 64Y should implement the contract by adding saved view support to SalesInvoiceCollectionFollowUpReportController, route, registry, and report-specific config partial.
