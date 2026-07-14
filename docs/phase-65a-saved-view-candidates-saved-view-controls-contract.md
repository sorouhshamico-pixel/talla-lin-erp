# Phase 65A — Prepare Saved View Candidates Saved View Controls Contract

## Status

Phase 65A prepares the saved view controls contract for the Phase 64Z locked target.

No implementation files are changed in this phase.

## Baseline

- Phase: Phase 64Z clean
- Commit: 6142d90
- Previous lock baseline: Phase 64Y clean / 17b5d7e / 1127 passed / 9732 assertions

## Locked target

- Key: saved-view-candidates
- View path: resources/views/reports/saved-view-candidates.blade.php
- Controller path: none; implemented through route closures
- Priority score: 60
- Has GET form at lock time: no
- Has filters at lock time: yes
- Has saved view controls terms at lock time: yes

## Proposed implementation contract

- Registry key: saved-view-candidates
- Config partial: reports.partials.saved-view-candidates-saved-view-controls-config
- Config partial path: resources/views/reports/partials/saved-view-candidates-saved-view-controls-config.blade.php
- View include: @include('reports.partials.saved-view-candidates-saved-view-controls-config')
- Shared controls partial: reports.partials.saved-view-controls
- Index route: reports.saved-view-candidates.index
- Markdown route: reports.saved-view-candidates.markdown
- Export route: reports.saved-view-candidates.json
- Saved view store route to add: reports.saved-view-candidates.saved-views.store
- Hidden fields: none

## Current state

- Target currently has GET form: no
- Target currently has saved view controls terms: yes
- Target currently has report-specific config partial: no
- Routes currently have index route: yes
- Routes currently have markdown route: yes
- Routes currently have json route: yes
- Routes currently have saved view store route: no

## Existing route names found in target view

- reports.saved-view-candidates.json
- reports.saved-view-candidates.markdown
- reports.saved-view-diagnostics.index
- reports.saved-view-rollout-selector.index

## Proposed test ids

- section_card: saved-view-candidates-saved-views-selector
- empty: saved-view-candidates-saved-views-empty
- form_card: saved-view-candidates-save-view-card
- form: saved-view-candidates-save-view-form
- name_input: saved-view-candidates-saved-view-name-input
- default_checkbox: saved-view-candidates-saved-view-default-checkbox
- save_button: saved-view-candidates-save-view-button
- list: saved-view-candidates-saved-views-list
- item: saved-view-candidates-saved-view-item
- open_link: saved-view-candidates-saved-view-open-link
- active_badge: saved-view-candidates-saved-view-active-badge
- default_badge: saved-view-candidates-saved-view-default-badge

## Acceptance criteria

Phase 65A is accepted when:

- the contract JSON exists
- the contract markdown exists
- the contract target matches the Phase 64Z lock
- the target view exists
- the current index, markdown, and json routes exist
- the proposed config partial path follows the saved view controls convention
- hidden fields are explicitly documented as empty
- full php artisan test passes

## Guard test

This phase is protected by:

ReportSavedViewPhase65ASavedViewCandidatesContractTest

## Next step

Phase 65B should implement this contract by adding the saved view store route, registry entry, report-specific config partial, and view include.
