# Phase 65C — Lock Next Saved View Rollout Target After Phase 65B

## Baseline

- Previous phase: Phase 65B clean
- Commit: 66315ee
- Tests: 1141 passed / 9966 assertions

## Locked target

- Key: cash-flow-dashboard-print
- View path: resources/views/reports/cash-flow-dashboard-print.blade.php
- Priority score: 40
- Registered at lock time: no
- Has GET form: no
- Has filters: yes
- Has saved view controls: no

## Proposed contract seed

- Registry key: cash-flow-dashboard-print
- Config partial: reports.partials.cash-flow-dashboard-print-saved-view-controls-config
- Config partial path: resources/views/reports/partials/cash-flow-dashboard-print-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Guardrails

- No shared saved-view partial changes in this phase.
- No static markers.
- No hidden markers.
- No full partial rewrites.
- Inspect target type before rollout because remaining candidates include print/tooling pages.
- Focused tests before full suite.

## Next step

Phase 65D should inspect the locked target and prepare a focused saved view controls contract or a documented skip/exclusion contract if the target is not eligible.
