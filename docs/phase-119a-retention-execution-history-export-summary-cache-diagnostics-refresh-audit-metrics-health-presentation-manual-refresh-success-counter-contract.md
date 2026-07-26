# Phase 119A — Manual Refresh Success Counter Contract

## Baseline

- Phase: Phase 118C
- Commit: `930dcf10a0de1545b69cda7d24b60d088137fbfb`
- Full suite: 2323 passed
- Assertions: 23535
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side success counter for accepted manual Health refresh requests that complete with a parsed and structurally valid payload.

Validated Healthy and Validated Unhealthy both count as success because the request and payload contract completed correctly.

## Element

- ID: `retention-audit-metrics-health-manual-refresh-successes`
- Prefix: `Manual refresh successes:`
- Initial text: `0`
- `aria-live="off"`

## State

- Variable: `manualRefreshSuccesses`
- Initial value: zero
- Minimum: zero
- Maximum: 999
- Integer only
- Page memory only
- No persistent storage

## Counted outcomes

- Validated Healthy
- Validated Unhealthy

## Non-counted outcomes

- Initial automatic request
- Ignored concurrent manual request
- HTTP error
- Network failure
- JSON parsing failure
- Payload validation failure

## Update rules

- Uses the existing manual request identity flow finalized in Phase 118C
- Manual identity remains available until result classification
- Increment occurs after payload validation
- Increment occurs before `requestSucceeded = true`
- One increment per validated manual request
- Counter clamps to 999

## Preserved legacy contract

The following strings remain unchanged:

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`

## Compatibility

All existing Health presentation, manual-attempt counter, request, validation, endpoint, authorization, database, and migration contracts remain unchanged.

No Timer, Polling, or Retry loop is added.

## Planned implementation

Phase 119B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 119B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 119B — Implement Manual Refresh Success Counter.
