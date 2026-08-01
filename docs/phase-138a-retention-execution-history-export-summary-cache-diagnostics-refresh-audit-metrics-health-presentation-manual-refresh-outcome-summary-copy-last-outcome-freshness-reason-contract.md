# Phase 138A — Manual Refresh Outcome Summary Copy Last Outcome Freshness Reason Contract

## Baseline

- Phase: Phase 137C
- Commit: `f1805e0b0f725363e03a2830e5697fb5360983ec`
- Full suite: 2620 passed
- Assertions: 27825
- Working tree: clean

## Classification

Documentation and tests only. No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Reason element

- ID: `retention-audit-metrics-health-manual-refresh-outcome-summary-copy-last-outcome-freshness-reason`
- Prefix: `Freshness reason:`
- Initial text: `No completed copy outcome yet.`
- `aria-live="polite"`

## Locked reason rules

- Unavailable: `No completed copy outcome yet.`
- Fresh: `The latest copy outcome is within the 14-minute freshness window.`
- Stale: `The latest copy outcome is older than the 14-minute freshness window.`
- Unknown state falls back to the unavailable reason
- Renderer runs after success, after failure, and during initialization
- Expected renderer invocation count: three

## Restrictions

- No Timer
- No Polling
- No Timeout
- No automatic refresh
- No automatic reset
- Existing render paths only

## Preserved behavior

- Freshness, age, timestamp, last outcome, success rate, and counters
- Three invocations for freshness, age, and timestamp renderers
- One timestamp assignment for resolved writes
- One timestamp assignment for rejected writes
- Clipboard API and Promise callbacks
- Phase 123B literal fallback
- Phase 134B through Phase 137B source ordering

## Planned implementation

Phase 138B may modify only the existing Partial and one focused implementation test.

## Next recommendation

Phase 138B — Implement Manual Refresh Outcome Summary Copy Last Outcome Freshness Reason.
