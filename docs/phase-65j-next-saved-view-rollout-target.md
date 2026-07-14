# Phase 65J — Exclude Internal Tooling Saved View Candidates And Lock Next Target

## Baseline

- Previous phase: Phase 65I clean
- Commit: f265104
- Tests: 1175 passed / 10324 assertions

## Selector change

- Internal saved-view tooling candidates are excluded from `ReportSavedViewRolloutSelector::prioritizedCandidates()`.
- Excluded internal tooling candidate count: 1
- Print-only exclusion remains active.

## Excluded internal tooling candidates

- saved-view-rollout-selector — resources/views/reports/saved-view-rollout-selector.blade.php

## Locked next non-tooling target

- Key: saved-view-rollout-target
- View path: resources/views/reports/saved-view-rollout-target.blade.php
- Priority score: 40
- Registered at lock time: no
- Has GET form: no
- Has filters: yes
- Has saved view controls: no

## Proposed contract seed

- Registry key: saved-view-rollout-target
- Config partial: reports.partials.saved-view-rollout-target-saved-view-controls-config
- Config partial path: resources/views/reports/partials/saved-view-rollout-target-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Next step

Phase 65K should inspect the locked target and prepare a focused saved view controls contract or a documented skip/exclusion contract if the target is not eligible.
