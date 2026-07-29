# Phase 125A — Manual Refresh Last Outcome Freshness Contract

## Baseline

- Phase: Phase 124C
- Commit: `3f7fcd3fbbbe5919edc91fc1ed26d6c26108b5d5`
- Full suite: 2413 passed
- Assertions: 24827
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side freshness classification derived from the most recent accepted manual refresh outcome timestamp.

## Element

- ID: `retention-audit-metrics-health-manual-refresh-last-outcome-freshness`
- Element: `span`
- Prefix: `Last manual refresh outcome freshness:`
- Initial text: `Unavailable`
- `aria-live="off"`
- Attribute: `data-freshness-state`
- Initial state: `unavailable`

## States

- `unavailable` → `Unavailable`
- `fresh` → `Fresh`
- `stale` → `Stale`

## State management

- Source: `lastManualRefreshOutcomeAt`
- Formatter: `formatLastManualRefreshOutcomeFreshness`
- Renderer: `renderLastManualRefreshOutcomeFreshness`
- Page memory only
- No persistent storage

## Thresholds

- Fresh: up to 14 minutes old
- Stale: 15 minutes or older
- Negative age: clamped to zero
- Missing or invalid timestamp: `unavailable`

## Update rules

Freshness renders when the accepted manual-refresh outcome timestamp updates.

It uses the same `completedAt` value as the timestamp and age fields.

It updates for Healthy, Unhealthy, and Failed outcomes.

It does not update for the initial automatic request, ignored concurrent manual request, or attempt increment alone.

No Timer, Polling, or periodic recalculation is added.

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 110B visual-state ordering remains preserved
- Phase 111B refresh timestamp remains preserved
- Phase 123B last-outcome timestamp remains preserved
- Phase 124B last-outcome age remains preserved

## Planned implementation

Phase 125B may modify only the existing Audit Metrics Health Partial and one focused Phase 125B implementation test.

Maximum modified files: two.

## Next recommendation

Phase 125B — Implement Manual Refresh Last Outcome Freshness.
