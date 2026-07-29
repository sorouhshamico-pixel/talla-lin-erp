# Phase 127A — Manual Refresh Outcome Summary Copy Contract

## Baseline

- Phase: Phase 126C
- Commit: `648c625ca97045e08ee78713374b796260cf6043`
- Full suite: 2441 passed
- Assertions: 25234
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define an explicit client-side copy action for the rendered manual refresh outcome summary.

## Copy button

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy`
- Element: `button`
- Type: `button`
- Initial label: `Copy summary`
- Initially disabled
- `aria-live="off"`

## Copy status

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status`
- Element: `span`
- Initial text: empty
- `aria-live="polite"`

## Copy source

- Summary element: `retention-audit-metrics-health-manual-refresh-outcome-summary`
- Value source: nested `span`
- `Not available` is never copied
- The visible prefix is not copied

## Labels

- Idle: `Copy summary`
- Success: `Copied`
- Failure: `Copy failed`
- Unavailable: `Summary unavailable`

## Clipboard contract

- Primary API: `navigator.clipboard.writeText`
- Requires a secure context and an available Clipboard API
- No `document.execCommand` fallback
- No hidden textarea fallback

## Availability

The copy button remains disabled while `data-summary-state="unavailable"`.

It becomes enabled for:

- Healthy
- Unhealthy
- Failed

The copy source is `manualRefreshOutcomeSummaryValue.textContent`.

## Interaction

Copy occurs only after an explicit button click.

It does not occur:

- During initial load
- On manual refresh completion
- During automatic requests

A resolved clipboard write sets `Copied`.

A rejected or unavailable clipboard write sets `Copy failed`.

No Timer or Polling is added.

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 123B literal fallback remains `lastManualRefreshOutcomeAt.toLocaleString();`
- Phase 126B summary format remains unchanged
- Phase 126B summary renderer remains unchanged

## Planned implementation

Phase 127B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 127B implementation test

Maximum modified files: two.

## Next recommendation

Phase 127B — Implement Manual Refresh Outcome Summary Copy.
