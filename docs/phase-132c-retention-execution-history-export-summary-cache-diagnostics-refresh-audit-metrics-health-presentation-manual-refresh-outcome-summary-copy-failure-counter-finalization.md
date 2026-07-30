# Phase 132C — Finalize Manual Refresh Outcome Summary Copy Failure Counter

## Baseline

- Phase: Phase 132B
- Commit: `fa511bb8104ddd3a5ff28aefd249b927e5d9f2ea`
- Full suite: 2535 passed
- Assertions: 26606
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked failure counter

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures`
- Prefix: `Copy failures:`
- Initial value: `0`
- Maximum value: `999`
- `aria-live="polite"`

## Locked state management

- Display element: `manualRefreshOutcomeSummaryCopyFailures`
- Variable: `manualRefreshOutcomeSummaryCopyFailureCount`
- Renderer: `renderManualRefreshOutcomeSummaryCopyFailures`
- Recorder: `recordManualRefreshOutcomeSummaryCopyFailure`
- Client memory only
- No persistent storage

## Locked counting behavior

Count only rejected Clipboard writes.

Do not count:

- Resolved Clipboard writes
- Eligible attempts before resolution
- Unavailable summary clicks
- Unsupported Clipboard clicks
- Initialization
- Manual refresh completion
- Automatic requests
- Status reset

The recorder is invoked exactly once inside the Promise failure callback and before the `Copy failed` status is set.

## Locked render behavior

- Integer only
- Non-negative only
- Clamped to `999`
- Rendered during initialization
- No Timer, Polling, or Timeout

## Preserved attempt counter

- Element: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts`
- Variable: `manualRefreshOutcomeSummaryCopyAttemptCount`
- Renderer: `renderManualRefreshOutcomeSummaryCopyAttempts`
- Recorder: `recordManualRefreshOutcomeSummaryCopyAttempt`
- Maximum value: `999`
- Increment remains before Clipboard write

## Preserved success counter

- Element: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes`
- Variable: `manualRefreshOutcomeSummaryCopySuccessCount`
- Renderer: `renderManualRefreshOutcomeSummaryCopySuccesses`
- Recorder: `recordManualRefreshOutcomeSummaryCopySuccess`
- Maximum value: `999`
- Increment remains inside the Promise success callback

## Preserved copy behavior

- Handler: `copyManualRefreshOutcomeSummary`
- Status resetter: `resetManualRefreshOutcomeSummaryCopyStatus`
- Clipboard API: `navigator.clipboard.writeText`
- Promise callbacks remain
- Success literal remains `setManualRefreshOutcomeSummaryCopyStatus('Copied');`
- Failure label remains `Copy failed`
- Unavailable label remains `Summary unavailable`
- No fallback
- No pre-`loadHealth()` `try/catch`

## Preserved availability feedback

- Formatter: `formatManualRefreshOutcomeSummaryCopyAvailability`
- Renderer: `renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback`
- States remain `unavailable`, `available`, `unsupported`
- Existing messages remain unchanged

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 114B through 120B `try/catch` ordering remains preserved
- Phase 123B literal fallback remains `lastManualRefreshOutcomeAt.toLocaleString();`
- Phase 126B summary format remains unchanged
- Phase 127B copy handler remains unchanged
- Phase 128B availability feedback remains unchanged
- Phase 129B status reset remains unchanged
- Phase 130B attempt counter remains unchanged
- Phase 131B success counter remains unchanged

## Implementation scope

Phase 132B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 132B implementation test

## Next recommendation

Phase 133A — Prepare Manual Refresh Outcome Summary Copy Success Rate Contract.
