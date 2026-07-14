# Phase 64N — Lock Next Saved View Rollout Target After Phase 64M

## Baseline

- Previous phase: Phase 64M clean
- Commit: b71b823
- Tests: 1071 passed / 8760 assertions

## Locked target

- Key: index
- View path: resources/views/reports/index.blade.php
- Priority score: 80
- Registered at lock time: no
- Has GET form: yes
- Has filters: yes
- Has saved view controls: no

## Proposed contract seed

- Registry key: index
- Config partial: reports.partials.index-saved-view-controls-config
- Config partial path: resources/views/reports/partials/index-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Guardrails

- No shared saved-view partial changes in this phase.
- No static markers.
- No hidden markers.
- No full partial rewrites.
- Focused tests before full suite.

## Next step

Phase 64O should inspect the locked target view and prepare a focused saved view controls contract.
