# Phase 122A — Manual Refresh Last Outcome Contract

## Baseline

- Phase: Phase 121C
- Commit: `687d344421bcc62a049548500f092bd033ed8389`
- Full suite: 2368 passed
- Assertions: 24174
- Working tree: clean
- Registered worktrees: one

## Classification

Documentation and tests only.

No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Purpose

Define a client-side field showing the result of the most recent accepted manual refresh.

## Element

- ID: `retention-audit-metrics-health-manual-refresh-last-outcome`
- Prefix: `Last manual refresh outcome:`
- Initial text: `Not available`
- `aria-live="polite"`
- State attribute: `data-outcome-state`
- Initial state: `unavailable`

## States

- `unavailable` → `Not available`
- `healthy` → `Healthy`
- `unhealthy` → `Requires attention`
- `failed` → `Failed`

## State management

- Variable: `lastManualRefreshOutcome`
- Renderer: `renderLastManualRefreshOutcome`
- Setter: `setLastManualRefreshOutcome`
- Page memory only
- No persistent storage

## Update rules

- Validated manual Healthy updates to `healthy`
- Validated manual Unhealthy updates to `unhealthy`
- Manual HTTP, Network, Parsing, or Validation failure updates to `failed`
- Initial automatic request does not update the field
- Ignored concurrent manual request does not update the field
- Manual attempt increment alone does not update the field
- One update per completed accepted manual request

## Preserved legacy contract

- `const loadHealth = async () => {`
- `refresh.addEventListener('click', loadHealth);`
- `loadHealth();`

## Compatibility

The manual attempt, success, failure, and success-rate metrics remain unchanged.

All existing Health presentation, request, validation, endpoint, authorization, database, and migration behavior remains unchanged.

No Timer, Polling, or Retry loop is added.

## Planned implementation

Phase 122B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 122B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful completion: commit and push immediately

## Next recommendation

Phase 122B — Implement Manual Refresh Last Outcome.
