# Phase 64W — Lock Next Saved View Rollout Target After Phase 64V

## Baseline

- Previous phase: Phase 64V clean
- Commit: a2a2777
- Tests: Phase 64V full suite completed before commit; exact count was not included in the pasted inspection output.

## Locked target

- Key: sales-invoice-collection-follow-ups
- View path: resources/views/reports/sales-invoice-collection-follow-ups.blade.php
- Priority score: 80
- Registered at lock time: no
- Has GET form: yes
- Has filters: yes
- Has saved view controls: no

## Proposed contract seed

- Registry key: sales-invoice-collection-follow-ups
- Config partial: reports.partials.sales-invoice-collection-follow-ups-saved-view-controls-config
- Config partial path: resources/views/reports/partials/sales-invoice-collection-follow-ups-saved-view-controls-config.blade.php
- Shared controls partial: reports.partials.saved-view-controls

## Guardrails

- No shared saved-view partial changes in this phase.
- No static markers.
- No hidden markers.
- No full partial rewrites.
- Focused tests before full suite.

## Next step

Phase 64X should inspect the locked target view and prepare a focused saved view controls contract.
