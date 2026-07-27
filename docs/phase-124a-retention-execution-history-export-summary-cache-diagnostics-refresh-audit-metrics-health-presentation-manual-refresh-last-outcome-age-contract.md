# Phase 124A — Manual Refresh Last Outcome Age Contract

## Baseline

- Phase: Phase 123C
- Commit: `5718588bbb2e9b11d5045a6c3fb8a388e45cd561`
- Full suite: 2398 passed
- Assertions: 24601
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side age label derived from the most recent accepted manual refresh outcome timestamp.

## Element

- ID: `retention-audit-metrics-health-manual-refresh-last-outcome-age`
- Element: `span`
- Prefix: `Last manual refresh outcome age:`
- Initial text: `Not available`
- `aria-live="off"`

## State management

- Source: `lastManualRefreshOutcomeAt`
- Formatter: `formatLastManualRefreshOutcomeAge`
- Renderer: `renderLastManualRefreshOutcomeAge`
- Page memory only
- No persistent storage

## Formatting

- Missing or invalid timestamp: `Not available`
- Less than one minute: `Less than 1 minute`
- Minutes: capped at 999
- Hours: capped at 999
- Days: capped at 999
- Negative age: clamped to zero
- Hours begin at 60 minutes
- Days begin at 1440 minutes

## Update rules

The age renders when the accepted manual-refresh outcome timestamp updates.

It uses the same completed-at value as the timestamp field.

It updates for:

- Healthy
- Unhealthy
- Failed

It does not update for:

- Initial automatic request
- Ignored concurrent manual request
- Manual attempt increment alone

No Timer, Polling, or periodic recalculation is added.

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`
- Phase 110B visual-state ordering remains preserved
- Phase 111B refresh timestamp remains preserved
- Phase 123B last-outcome timestamp remains preserved

## Compatibility

All previous manual refresh metrics, last-outcome behavior, and last-outcome timestamp behavior remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, Cache, and Logging behavior remains unchanged.

## Planned implementation

Phase 124B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 124B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 124B — Implement Manual Refresh Last Outcome Age.
