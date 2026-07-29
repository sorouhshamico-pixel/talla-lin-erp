# Phase 126C — Finalize Manual Refresh Outcome Summary

## Baseline

- Phase: Phase 126B
- Commit: `293cb72860a66d06916c020146aee17728967278`
- Full suite: 2436 passed
- Assertions: 25144
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary`
- Element: `p`
- Prefix: `Manual refresh outcome summary:`
- Initial text: `Not available`
- `aria-live="polite"`
- Attribute: `data-summary-state`
- Initial state: `unavailable`

## Locked states

- `unavailable` → `Not available`
- `healthy` → `Healthy`
- `unhealthy` → `Requires attention`
- `failed` → `Failed`

## Locked summary format

Segments are joined using ` · `:

- Outcome label
- Formatted timestamp
- Formatted age
- Freshness label

The implementation reuses:

- `formatLastManualRefreshOutcomeTimestamp`
- `formatLastManualRefreshOutcomeAge`
- `formatLastManualRefreshOutcomeFreshness`

No formatter business logic is duplicated.

## Locked update behavior

The summary uses the same `completedAt` value as the timestamp, age, and freshness fields.

`renderManualRefreshOutcomeSummary(completedAt)` is called exactly once from `setLastManualRefreshOutcomeTimestamp()`.

The summary updates for Healthy, Unhealthy, and Failed outcomes.

It does not update for the initial automatic request, ignored concurrent manual request, or attempt increment alone.

No Timer, Polling, or periodic recalculation is added.

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 110B visual-state ordering remains preserved
- Phase 111B refresh timestamp remains preserved
- Phase 123B literal fallback remains `lastManualRefreshOutcomeAt.toLocaleString();`
- Phase 123B last-outcome timestamp remains preserved
- Phase 124B last-outcome age remains preserved
- Phase 125B last-outcome freshness remains preserved

## Compatibility

All previous manual refresh metrics, outcome, timestamp, age, and freshness behavior remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, Cache, and Logging behavior remains unchanged.

## Implementation scope

Phase 126B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 126B implementation test

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 127A — Prepare Manual Refresh Outcome Summary Copy Contract.
