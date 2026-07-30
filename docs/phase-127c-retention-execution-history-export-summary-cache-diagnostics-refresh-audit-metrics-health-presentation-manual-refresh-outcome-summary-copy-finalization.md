# Phase 127C — Finalize Manual Refresh Outcome Summary Copy

## Baseline

- Phase: Phase 127B
- Commit: `cadea67f9dcd049da3440e1d8f957f305964cb8c`
- Full suite: 2452 passed
- Assertions: 25365
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Locked copy control

- Button ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy`
- Label: `Copy summary`
- Initially disabled
- Status ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status`
- Status uses `aria-live="polite"`

## Locked status labels

- Success: `Copied`
- Failure: `Copy failed`
- Unavailable: `Summary unavailable`

## Locked copy source

The copied value is the nested summary `span` text only.

The visible `Manual refresh outcome summary:` prefix is not copied.

`Not available` is never copied.

## Locked Clipboard behavior

- API: `navigator.clipboard.writeText`
- Secure context required
- Explicit click required
- Promise success and failure callbacks are used
- No `try/catch` is added before `loadHealth()`
- No `document.execCommand`
- No textarea fallback
- No storage

## Locked availability

`renderManualRefreshOutcomeSummaryCopyAvailability()` disables the button when the summary is unavailable.

It enables the button for Healthy, Unhealthy, and Failed summaries.

The availability sources are:

- `data-summary-state`
- `manualRefreshOutcomeSummaryValue.textContent`

## Locked interaction

- Handler: `copyManualRefreshOutcomeSummary`
- Status setter: `setManualRefreshOutcomeSummaryCopyStatus`
- Locked success literal: `setManualRefreshOutcomeSummaryCopyStatus('Copied');`
- No copy during initial load
- No copy during refresh completion
- No copy during automatic requests
- No Timer or Polling

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 114B through 120B `try/catch` ordering remains preserved
- Phase 123B literal fallback remains `lastManualRefreshOutcomeAt.toLocaleString();`
- Phase 126B summary format remains unchanged
- Phase 126B summary renderer remains unchanged

## Implementation scope

Phase 127B modified only:

- Existing Audit Metrics Health Partial
- One focused Phase 127B implementation test

## Next recommendation

Phase 128A — Prepare Manual Refresh Outcome Summary Copy Availability Feedback Contract.
