# Phase 131A — Manual Refresh Outcome Summary Copy Success Counter Contract

## Baseline

- Phase: Phase 130C
- Commit: `e2e8bfc299aa5a3dbea1526602b6d2a1a1dc426a`
- Full suite: 2506 passed
- Assertions: 26173
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side counter for successfully resolved manual refresh outcome summary Clipboard writes.

## Counter element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-successes`
- Element: `span`
- Prefix: `Copy successes:`
- Initial value: `0`
- `aria-live="polite"`

## State management

- Variable: `manualRefreshOutcomeSummaryCopySuccessCount`
- Renderer: `renderManualRefreshOutcomeSummaryCopySuccesses`
- Recorder: `recordManualRefreshOutcomeSummaryCopySuccess`
- Initial value: `0`
- Maximum value: `999`
- Client memory only
- No persistent storage

## Counting rules

Count only:

- Resolved `navigator.clipboard.writeText` calls

Do not count:

- Rejected Clipboard writes
- Eligible attempts before resolution
- Unavailable summary clicks
- Unsupported Clipboard clicks
- Initialization
- Manual refresh completion
- Automatic requests
- Status reset

The increment occurs once inside the Promise success callback and before the `Copied` status is set.

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

## Planned implementation

Phase 131B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 131B implementation test

Maximum modified files: two.

## Next recommendation

Phase 131B — Implement Manual Refresh Outcome Summary Copy Success Counter.
