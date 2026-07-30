# Phase 129A — Manual Refresh Outcome Summary Copy Status Reset Contract

## Baseline

- Phase: Phase 128C
- Commit: `351e1067fc8adb3a0391426f9dae0c6a24c08618`
- Full suite: 2473 passed
- Assertions: 25690
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define deterministic reset behavior for the manual refresh outcome summary copy status when the summary or copy availability changes.

## Locked status element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-status`
- Element: `span`
- `aria-live="polite"`
- Initial text: empty

## Locked status labels

- Idle: empty string
- Success: `Copied`
- Failure: `Copy failed`
- Unavailable: `Summary unavailable`

## Reset sources

- `manualRefreshOutcomeSummary.dataset.summaryState`
- `manualRefreshOutcomeSummaryValue.textContent`
- `manualRefreshOutcomeSummaryCopyAvailability.dataset.copyAvailability`

## State management

- Resetter: `resetManualRefreshOutcomeSummaryCopyStatus`
- Existing setter reused: `setManualRefreshOutcomeSummaryCopyStatus`
- Existing availability renderer reused: `renderManualRefreshOutcomeSummaryCopyAvailability`
- Client memory only
- No persistent storage

## Reset rules

- Reset to idle when the summary becomes available
- Reset to unavailable when the summary becomes unavailable
- Reset to unavailable when Clipboard is unsupported
- Reset before a new copy attempt
- Preserve success until summary or availability changes
- Preserve failure until summary or availability changes
- Reset during initialization
- Do not reset on an automatic request that does not change the summary
- No Timer, Polling, or Timeout

## Preserved copy behavior

- Handler: `copyManualRefreshOutcomeSummary`
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
- States: `unavailable`, `available`, `unsupported`
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

## Planned implementation

Phase 129B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 129B implementation test

Maximum modified files: two.

## Next recommendation

Phase 129B — Implement Manual Refresh Outcome Summary Copy Status Reset.
