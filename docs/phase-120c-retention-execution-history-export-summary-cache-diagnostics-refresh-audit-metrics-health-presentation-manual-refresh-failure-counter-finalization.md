# Phase 120C — Finalize Manual Refresh Failure Counter

## Baseline

- Phase: Phase 120B
- Commit: `d199074847a61ac01e4127cc2d7d7f0476e79df1`
- Full suite: 2350 passed
- Assertions: 23893
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked element

- ID: `retention-audit-metrics-health-manual-refresh-failures`
- Prefix: `Manual refresh failures:`
- Initial value: `0`
- `aria-live="off"`

## Locked state and helpers

- State variable: `manualRefreshFailures`
- Renderer: `renderManualRefreshFailures`
- Recorder: `recordManualRefreshFailure`
- Range: 0 through 999
- Invalid values normalize to zero
- Page memory only
- No persistent storage

## Locked classification

Counts:

- HTTP error
- Network failure
- JSON parsing failure
- Payload validation failure

Does not count:

- Validated Healthy
- Validated Unhealthy
- Initial automatic request
- Ignored concurrent manual request

## Locked update order

The failure counter increments:

- Inside `catch`
- Only when `isManualRefresh` is true
- Before Network status handling
- Before `setUnavailable()`
- Before `finally`
- Once per failed manual request
- Never inside `finally`

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`

## Compatibility

The manual-attempt and manual-success counters remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, Cache, and Logging behavior remain unchanged.

No Timer, Polling, or Retry loop is added.

## Implementation scope

Phase 120B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 120B implementation test

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 121A — Prepare Manual Refresh Success Rate Contract.
