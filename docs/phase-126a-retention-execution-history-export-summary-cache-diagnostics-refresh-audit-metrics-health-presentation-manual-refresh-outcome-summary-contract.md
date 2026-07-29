# Phase 126A — Manual Refresh Outcome Summary Contract

## Baseline

- Phase: Phase 125C
- Commit: `1112afaf81355872e41731f5374e168eec41eeba`
- Full suite: 2427 passed
- Assertions: 25034
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a compact client-side summary combining the most recent accepted manual refresh outcome, timestamp, age, and freshness.

## Element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary`
- Element: `p`
- Prefix: `Manual refresh outcome summary:`
- Initial text: `Not available`
- `aria-live="polite"`
- Attribute: `data-summary-state`
- Initial state: `unavailable`

## Summary states

- `unavailable` → `Not available`
- `healthy` → `Healthy`
- `unhealthy` → `Requires attention`
- `failed` → `Failed`

## State management

- Sources: `lastManualRefreshOutcome` and `lastManualRefreshOutcomeAt`
- Formatter: `formatManualRefreshOutcomeSummary`
- Renderer: `renderManualRefreshOutcomeSummary`
- Page memory only
- No persistent storage

## Summary format

Segments are joined with ` · `:

- Outcome label
- Formatted timestamp
- Formatted age
- Freshness label

The implementation reuses the existing timestamp, age, and freshness formatters. It must not duplicate their business logic.

Missing or invalid state renders `Not available`.

## Update rules

The summary renders after the outcome and timestamp state are updated.

It updates for Healthy, Unhealthy, and Failed outcomes.

It does not update for the initial automatic request, ignored concurrent manual request, or attempt increment alone.

No Timer, Polling, or periodic recalculation is added.

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 110B visual-state ordering remains preserved
- Phase 111B refresh timestamp remains preserved
- Phase 123B last-outcome timestamp remains preserved
- Phase 124B last-outcome age remains preserved
- Phase 125B last-outcome freshness remains preserved

## Planned implementation

Phase 126B may modify only the existing Audit Metrics Health Partial and one focused Phase 126B implementation test.

Maximum modified files: two.

## Next recommendation

Phase 126B — Implement Manual Refresh Outcome Summary.
