# Phase 64E — Lock Next Saved View Rollout Target After Phase 64D

## Baseline

- Previous phase: Phase 64D clean
- Commit: 34feb2c
- Tests: 1029 passed / 8078 assertions

## Locked target

- Key: supplier-purchase-invoice-aging
- View path: resources/views/reports/supplier-purchase-invoice-aging.blade.php
- Priority score: 100
- Registered at lock time: no
- Has GET form: yes
- Has filters: yes
- Has saved view controls: yes

## Proposed contract seed

- Registry key: supplier-purchase-invoice-aging
- Config partial: reports.partials.supplier-purchase-invoice-aging-saved-view-controls-config
- Config partial path: resources/views/reports/partials/supplier-purchase-invoice-aging-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Guardrails

- No shared saved-view partial changes in this phase.
- No static markers.
- No hidden markers.
- No full partial rewrites.
- Focused tests before full suite.

## Next step

Phase 64F should inspect the locked target view and prepare a focused saved view controls contract.
