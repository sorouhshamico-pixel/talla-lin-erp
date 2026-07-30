# Phase 130A — Manual Refresh Outcome Summary Copy Attempt Counter Contract

## Baseline

- Phase: Phase 129C
- Commit: `965295dc338301b24c35200781bca5fff8de73b3`
- Full suite: 2489 passed
- Assertions: 25921
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side counter for explicit, eligible manual refresh outcome summary copy attempts.

## Counter element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-attempts`
- Element: `span`
- Prefix: `Copy attempts:`
- Initial value: `0`
- `aria-live="polite"`

## State management

- Variable: `manualRefreshOutcomeSummaryCopyAttempts`
- Renderer: `renderManualRefreshOutcomeSummaryCopyAttempts`
- Recorder: `recordManualRefreshOutcomeSummaryCopyAttempt`
- Initial value: `0`
- Maximum value: `999`
- Client memory only
- No persistent storage

## Counting rules

Count:

- Eligible explicit copy clicks
- Resolved Clipboard writes
- Rejected Clipboard writes

Do not count:

- Clicks while the summary is unavailable
- Clicks when Clipboard is unsupported
- Initialization
- Manual refresh completion
- Automatic requests
- Status reset

The increment occurs once and before `navigator.clipboard.writeText`.

## Render rules

- Integer only
- Non-negative only
- Clamped to `999`
- Rendered during initialization
- No Timer, Polling, or Timeout

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

## Planned implementation

Phase 130B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 130B implementation test

Maximum modified files: two.

## Next recommendation

Phase 130B — Implement Manual Refresh Outcome Summary Copy Attempt Counter.
