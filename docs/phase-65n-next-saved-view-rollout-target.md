# Phase 65N — Exclude Reports Center Navigation Hub And Lock Next Target

## Baseline

- Previous phase: Phase 65M clean
- Commit: 2390575
- Tests: 1195 passed / 10474 assertions

## Selector change

- `center` is excluded from `ReportSavedViewRolloutSelector::prioritizedCandidates()`.
- Internal saved-view tooling exclusions remain active.
- Print-only exclusion remains active.
- Excluded navigation hub candidate count: 1

## Excluded navigation hub candidates

- center — resources/views/reports/center.blade.php

## Locked next non-navigation target

- Key: financial-dashboard
- View path: resources/views/reports/financial-dashboard.blade.php
- Priority score: 10
- Registered at lock time: no
- Has GET form: no
- Has filters: no
- Has saved view controls: no

## Proposed contract seed

- Registry key: financial-dashboard
- Config partial: reports.partials.financial-dashboard-saved-view-controls-config
- Config partial path: resources/views/reports/partials/financial-dashboard-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Next step

Phase 65O should inspect the locked target and prepare a focused saved view controls contract or a documented skip/exclusion contract if the target is not eligible.
