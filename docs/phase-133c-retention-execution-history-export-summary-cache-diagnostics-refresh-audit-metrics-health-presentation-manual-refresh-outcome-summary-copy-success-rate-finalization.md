# Phase 133C — Finalize Manual Refresh Outcome Summary Copy Success Rate

## Baseline

- Phase: Phase 133B
- Commit: `eae46af1b0eddf56063083ed91981facfe8b7cdf`
- Full suite: 2551 passed
- Assertions: 26844
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked success rate

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-success-rate`
- Prefix: `Copy success rate:`
- Initial value: `Not available`
- Renderer: `renderManualRefreshOutcomeSummaryCopySuccessRate`
- `aria-live="polite"`

## Locked calculation

- Completed writes = successes + failures
- Success rate = successes ÷ completed writes × 100
- Attempts are excluded from the denominator
- Unavailable and unsupported clicks are excluded
- No completed writes displays `Not available`
- Precision: one decimal place
- Range: 0% to 100%
- Finite number required

## Locked rendering

The renderer is invoked exactly three times:

- After success recording
- After failure recording
- During initialization

No Timer, Polling, Timeout, or persistent storage.

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

## Implementation scope

Phase 133B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 133B implementation test

## Next recommendation

Phase 134A — Prepare Manual Refresh Outcome Summary Copy Last Outcome Contract.
