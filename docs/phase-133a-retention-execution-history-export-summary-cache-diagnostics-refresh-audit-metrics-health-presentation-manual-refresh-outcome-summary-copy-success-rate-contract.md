# Phase 133A — Manual Refresh Outcome Summary Copy Success Rate Contract

## Baseline

- Phase: Phase 132C
- Commit: `613c4f83254fd1a98b8d6e6fb0a2c3f7b1d7694d`
- Full suite: 2540 passed
- Assertions: 26714
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side success rate derived from completed Clipboard writes.

## Rate element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate`
- Prefix: `Copy success rate:`
- Initial value: `Not available`
- `aria-live="polite"`

## Calculation

- Completed writes = successes + failures
- Success rate = successes ÷ completed writes × 100
- Attempts are excluded from the denominator
- Unavailable and unsupported clicks are excluded
- No completed writes displays `Not available`
- Precision: one decimal place
- Range: 0% to 100%

## Rendering

- Renderer: `renderManualRefreshOutcomeSummaryCopySuccessRate`
- Runs on initialization
- Runs after success recording
- Runs after failure recording
- Output format: `0.0%`
- No Timer, Polling, or Timeout
- Client memory only

## Preserved counters

The attempt, success, and failure counters remain unchanged, including their maximum value of `999`.

## Preserved copy behavior

- `copyManualRefreshOutcomeSummary`
- `resetManualRefreshOutcomeSummaryCopyStatus`
- `navigator.clipboard.writeText`
- Promise success and failure callbacks
- `setManualRefreshOutcomeSummaryCopyStatus('Copied');`
- `Copy failed`
- `Summary unavailable`
- No fallback
- No pre-`loadHealth()` `try/catch`

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 114B through 120B source ordering
- Phase 123B fallback literal
- Phase 130B attempt counter
- Phase 131B success counter
- Phase 132B failure counter

## Planned implementation

Phase 133B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 133B implementation test

Maximum modified files: two.

## Next recommendation

Phase 133B — Implement Manual Refresh Outcome Summary Copy Success Rate.
