# Phase 121A — Manual Refresh Success Rate Contract

## Baseline

- Phase: Phase 120C
- Commit: `60384c9ebc1a827a353e1ccf3e764a85a6bec1fd`
- Full suite: 2355 passed
- Assertions: 23981
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side derived success rate for accepted manual Health refresh attempts.

Formula:

`manualRefreshSuccesses / manualRefreshAttempts × 100`

## Element

- ID: `retention-audit-metrics-health-manual-refresh-success-rate`
- Prefix: `Manual refresh success rate:`
- Initial text: `Not available`
- `aria-live="off"`

## Source counters

- Denominator: `manualRefreshAttempts`
- Numerator: `manualRefreshSuccesses`
- `manualRefreshFailures` is not directly used in the formula

## Formatting

- Zero attempts: `Not available`
- Minimum: 0%
- Maximum: 100%
- Maximum fractional digits: one
- Locale-aware formatting when available
- Deterministic fallback formatting
- Percentage suffix: `%`

## Examples

- 0 attempts and 0 successes: `Not available`
- 1 attempt and 1 success: `100%`
- 1 attempt and 0 successes: `0%`
- 3 attempts and 2 successes: `66.7%`

## Update rules

Render after:

- Manual attempt increment
- Manual success increment
- Manual failure increment

The initial automatic request does not change the rate.

An ignored concurrent manual request does not change the rate.

State remains page-memory-only with no persistence.

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`

## Compatibility

The manual attempt, success, and failure counters remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, and migration behavior remains unchanged.

No Timer, Polling, or Retry loop is added.

## Planned implementation

Phase 121B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 121B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 121B — Implement Manual Refresh Success Rate.
