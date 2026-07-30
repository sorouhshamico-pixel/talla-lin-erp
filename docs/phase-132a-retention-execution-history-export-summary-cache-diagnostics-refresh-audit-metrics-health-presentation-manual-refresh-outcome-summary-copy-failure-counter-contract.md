# Phase 132A — Manual Refresh Outcome Summary Copy Failure Counter Contract

## Baseline

- Phase: Phase 131C
- Commit: `31ce8dad4c9254d9327033960f9300a152f8c6d1`
- Full suite: 2523 passed
- Assertions: 26438
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side counter for rejected manual refresh outcome summary Clipboard writes.

## Counter element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-failures`
- Element: `span`
- Prefix: `Copy failures:`
- Initial value: `0`
- `aria-live="polite"`

## State management

- Variable: `manualRefreshOutcomeSummaryCopyFailureCount`
- Renderer: `renderManualRefreshOutcomeSummaryCopyFailures`
- Recorder: `recordManualRefreshOutcomeSummaryCopyFailure`
- Initial value: `0`
- Maximum value: `999`
- Client memory only
- No persistent storage

## Counting rules

Count only:

- Rejected `navigator.clipboard.writeText` calls

Do not count:

- Resolved Clipboard writes
- Eligible attempts before resolution
- Unavailable summary clicks
- Unsupported Clipboard clicks
- Initialization
- Manual refresh completion
- Automatic requests
- Status reset

The increment occurs once inside the Promise failure callback and before the `Copy failed` status is set.

## Render rules

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

## Planned implementation

Phase 132B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 132B implementation test

Maximum modified files: two.

## Next recommendation

Phase 132B — Implement Manual Refresh Outcome Summary Copy Failure Counter.
