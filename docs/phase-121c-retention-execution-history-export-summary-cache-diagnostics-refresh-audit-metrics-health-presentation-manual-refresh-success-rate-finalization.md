# Phase 121C — Finalize Manual Refresh Success Rate

## Baseline

- Phase: Phase 121B
- Commit: `01cb1782b2a8dda89fd9fb46fa73fa76224c768f`
- Full suite: 2364 passed
- Assertions: 24095
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked element

- ID: `retention-audit-metrics-health-manual-refresh-success-rate`
- Prefix: `Manual refresh success rate:`
- Initial text: `Not available`
- `aria-live="off"`

## Locked calculation

Formula:

`manualRefreshSuccesses / manualRefreshAttempts × 100`

- Denominator: `manualRefreshAttempts`
- Numerator: `manualRefreshSuccesses`
- `manualRefreshFailures` is not directly used
- Zero attempts: `Not available`
- Range: 0% through 100%
- Maximum fractional digits: one
- Locale-aware formatting with deterministic fallback

## Locked rendering

- Formatter: `manualRefreshRateFormatter`
- Renderer: `renderManualRefreshSuccessRate`
- Render after manual attempt increment
- Render after manual success increment
- Render after manual failure increment
- Exactly three recorder-level render calls

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`

## Compatibility

The manual attempt, success, and failure counters remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, Cache, and Logging behavior remains unchanged.

No Timer, Polling, or Retry loop is added.

## Implementation scope

Phase 121B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 121B implementation test

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 122A — Prepare Manual Refresh Last Outcome Contract.
