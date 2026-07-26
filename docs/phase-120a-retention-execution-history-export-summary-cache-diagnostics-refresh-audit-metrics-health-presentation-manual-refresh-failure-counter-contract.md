# Phase 120A — Manual Refresh Failure Counter Contract

## Baseline

- Phase: Phase 119C
- Commit: `a38eb02c18a1e53ba992b2d0c8f49edd42f8ebe0`
- Full suite: 2339 passed
- Assertions: 23754
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side failure counter for accepted manual Health refresh requests that fail before producing a parsed and structurally valid payload.

## Element

- ID: `retention-audit-metrics-health-manual-refresh-failures`
- Prefix: `Manual refresh failures:`
- Initial text: `0`
- `aria-live="off"`

## State

- Variable: `manualRefreshFailures`
- Initial value: zero
- Minimum: zero
- Maximum: 999
- Integer only
- Page memory only
- No persistent storage

## Counted outcomes

- HTTP error
- Network failure
- JSON parsing failure
- Payload validation failure

## Non-counted outcomes

- Validated Healthy
- Validated Unhealthy
- Initial automatic request
- Ignored concurrent manual request

## Update rules

- Uses the existing manual request identity flow
- Manual identity remains available for failure classification
- Increment occurs in `catch` for manual requests
- One increment per failed manual request
- No increment in `finally`
- Counter clamps to 999

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`

## Compatibility

The manual-attempt and manual-success counters remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, and migration contracts remain unchanged.

No Timer, Polling, or Retry loop is added.

## Planned implementation

Phase 120B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 120B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 120B — Implement Manual Refresh Failure Counter.
