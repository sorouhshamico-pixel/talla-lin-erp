# Phase 64H — Lock Next Saved View Rollout Target After Phase 64G

## Baseline

- Previous phase: Phase 64G clean
- Commit: 23006ae
- Tests: 1043 passed / 8301 assertions

## Locked target

- Key: supplier-purchase-invoice-aging-drilldown
- View path: resources/views/reports/supplier-purchase-invoice-aging-drilldown.blade.php
- Priority score: 100
- Registered at lock time: no
- Has GET form: yes
- Has filters: yes
- Has saved view controls: yes

## Proposed contract seed

- Registry key: supplier-purchase-invoice-aging-drilldown
- Config partial: reports.partials.supplier-purchase-invoice-aging-drilldown-saved-view-controls-config
- Config partial path: resources/views/reports/partials/supplier-purchase-invoice-aging-drilldown-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Guardrails

- No shared saved-view partial changes in this phase.
- No static markers.
- No hidden markers.
- No full partial rewrites.
- Focused tests before full suite.

## Next step

Phase 64I should inspect the locked target view and prepare a focused saved view controls contract.
