# Phase 125C — Finalize Manual Refresh Last Outcome Freshness

## Baseline

- Phase: Phase 125B
- Commit: `a53a65b81b8d73dd89212c0b7d352a94aec5375d`
- Full suite: 2423 passed
- Assertions: 24946
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked element

- ID: `retention-audit-metrics-health-manual-refresh-last-outcome-freshness`
- Element: `span`
- Prefix: `Last manual refresh outcome freshness:`
- Initial text: `Unavailable`
- `aria-live="off"`
- Attribute: `data-freshness-state`
- Initial state: `unavailable`

## Locked states

- `unavailable` → `Unavailable`
- `fresh` → `Fresh`
- `stale` → `Stale`

## Locked state management

- Source: `lastManualRefreshOutcomeAt`
- Formatter: `formatLastManualRefreshOutcomeFreshness`
- Renderer: `renderLastManualRefreshOutcomeFreshness`
- Page memory only
- No persistent storage

## Locked thresholds

- Fresh: up to 14 minutes old
- Stale: 15 minutes or older
- Negative age clamped to zero
- Missing or invalid timestamp: `unavailable`

## Locked update behavior

The freshness renderer uses the same `completedAt` value as the timestamp and age fields.

It is called exactly once from `setLastManualRefreshOutcomeTimestamp()`.

Freshness updates for Healthy, Unhealthy, and Failed outcomes.

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

## Compatibility

All previous manual refresh metrics, outcome, timestamp, and age behavior remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, Cache, and Logging behavior remains unchanged.

## Implementation scope

Phase 125B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 125B implementation test

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 126A — Prepare Manual Refresh Outcome Summary Contract.
