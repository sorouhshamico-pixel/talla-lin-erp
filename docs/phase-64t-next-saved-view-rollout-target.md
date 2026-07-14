# Phase 64T — Lock Next Saved View Rollout Target After Phase 64S

## Baseline

- Previous phase: Phase 64S clean
- Commit: 6016ab9
- Tests: 1099 passed / 9240 assertions

## Locked target

- Key: receivable-payable-aging-dashboard
- View path: resources/views/reports/receivable-payable-aging-dashboard.blade.php
- Priority score: 80
- Registered at lock time: no
- Has GET form: yes
- Has filters: yes
- Has saved view controls: no

## Proposed contract seed

- Registry key: receivable-payable-aging-dashboard
- Config partial: reports.partials.receivable-payable-aging-dashboard-saved-view-controls-config
- Config partial path: resources/views/reports/partials/receivable-payable-aging-dashboard-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Guardrails

- No shared saved-view partial changes in this phase.
- No static markers.
- No hidden markers.
- No full partial rewrites.
- Focused tests before full suite.

## Next step

Phase 64U should inspect the locked target view and prepare a focused saved view controls contract.
