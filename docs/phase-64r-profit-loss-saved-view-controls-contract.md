# Phase 64R — Prepare Profit Loss Saved View Controls Contract

## Status

Phase 64R prepares the saved view controls contract for the Phase 64Q locked target.

No report implementation files are changed in this phase.

## Baseline

- Phase: Phase 64Q clean
- Commit: 8098e93
- Tests: 1089 passed / 9018 assertions

## Locked target

- Key: profit-loss
- View path: resources/views/reports/profit-loss.blade.php
- Controller path: app/Http/Controllers/ProfitLossReportController.php
- Priority score: 80
- Has saved view controls at lock time: no

## Proposed registry and implementation contract

- Registry key: profit-loss
- Config partial: reports.partials.profit-loss-saved-view-controls-config
- Config partial path: resources/views/reports/partials/profit-loss-saved-view-controls-config.blade.php
- View include: @include('reports.partials.profit-loss-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.profit-loss
- Export route: reports.profit-loss.export
- Saved view store route to add: reports.profit-loss.saved-views.store

## Candidate hidden fields

- from_date
- to_date
- branch_id

## Current state

- Target currently has GET filters: yes
- Target currently has saved view controls: no
- Controller currently has REPORT_KEY: no
- Controller currently uses ReportSavedViewService: no
- Controller currently has storeSavedView method: no
- Controller currently has export method: yes
- Routes currently have saved view store route: no

## Existing route names found in target view

- reports.profit-loss
- reports.profit-loss.export

## Proposed test ids

- section_card: profit-loss-saved-views-selector
- empty: profit-loss-saved-views-empty
- form_card: profit-loss-save-view-card
- form: profit-loss-save-view-form
- name_input: profit-loss-saved-view-name-input
- default_checkbox: profit-loss-saved-view-default-checkbox
- save_button: profit-loss-save-view-button
- list: profit-loss-saved-views-list
- item: profit-loss-saved-view-item
- open_link: profit-loss-saved-view-open-link
- active_badge: profit-loss-saved-view-active-badge
- default_badge: profit-loss-saved-view-default-badge

## Acceptance criteria

Phase 64R is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64Q lock
- the target view exists
- the controller exists
- the current index and export routes exist
- the proposed config partial path follows the saved view controls convention
- candidate hidden fields are documented
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase64RProfitLossContractTest

## Next step

Phase 64S should implement the contract by adding saved view support to ProfitLossReportController, route, registry, and report-specific config partial.
