# Phase 128A — Manual Refresh Outcome Summary Copy Availability Feedback Contract

## Baseline

- Phase: Phase 127C
- Commit: `18103b49541e998af6df5133f9b0ae15ea94e532`
- Full suite: 2457 passed
- Assertions: 25458
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define visible availability feedback for the manual refresh outcome summary copy action without changing existing copy behavior.

## Feedback element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-availability`
- Element: `span`
- Initial text: `Copy unavailable until a manual refresh completes.`
- `aria-live="polite"`
- Attribute: `data-copy-availability`
- Initial state: `unavailable`

## States

### Unavailable

- Text: `Copy unavailable until a manual refresh completes.`
- Copy button disabled

### Available

- Text: `Summary ready to copy.`
- Copy button enabled

### Unsupported

- Text: `Clipboard access is unavailable in this browser context.`
- Copy button disabled

## Sources

Availability is derived from:

- `manualRefreshOutcomeSummary.dataset.summaryState`
- `manualRefreshOutcomeSummaryValue.textContent`
- `window.isSecureContext`
- `navigator.clipboard`
- `navigator.clipboard.writeText`

## State management

- Formatter: `formatManualRefreshOutcomeSummaryCopyAvailability`
- Renderer: `renderManualRefreshOutcomeSummaryCopyAvailabilityFeedback`
- Existing copy availability renderer remains reused
- Client memory only
- No persistent storage

## Render rules

Feedback renders:

- During initialization
- Whenever the summary changes

It does not require:

- A copy attempt
- Timer
- Polling
- Periodic recalculation

## Preserved copy behavior

- Handler remains `copyManualRefreshOutcomeSummary`
- Clipboard API remains `navigator.clipboard.writeText`
- Promise success and failure callbacks remain
- Success literal remains `setManualRefreshOutcomeSummaryCopyStatus('Copied');`
- Failure label remains `Copy failed`
- Unavailable label remains `Summary unavailable`
- No clipboard fallback is added

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 114B through 120B `try/catch` ordering remains preserved
- Phase 123B literal fallback remains `lastManualRefreshOutcomeAt.toLocaleString();`
- Phase 126B summary format remains unchanged
- Phase 127B copy handler remains unchanged

## Planned implementation

Phase 128B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 128B implementation test

Maximum modified files: two.

## Next recommendation

Phase 128B — Implement Manual Refresh Outcome Summary Copy Availability Feedback.
