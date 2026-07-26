# Phase 118A — Manual Refresh Attempt Counter Contract

## Baseline

- Phase 117C
- Commit: `73746dc3580ceba54b855d2fde800e009c516e73`
- Working tree: clean
- One registered worktree

## Classification

Documentation and tests only. No runtime, backend, database, migration, controller, route, View, or Health class changes.

## Contract

- Element ID: `retention-audit-metrics-health-manual-refresh-attempts`
- Prefix: `Manual refresh attempts:`
- Initial value: `0`
- State variable: `manualRefreshAttempts`
- Range: 0 through 999
- Page memory only
- Initial automatic request does not count
- Accepted manual refresh counts once
- Ignored concurrent manual refresh does not count
- All accepted manual outcomes count, including Healthy, Unhealthy, HTTP, Network, parsing, and validation failures
- Increment occurs after the concurrency guard and before request execution
- Request function accepts a manual flag
- Manual flag defaults to false
- Button click passes true
- Initial request passes false
- No Polling, retry loop, persistence, or backend changes

## Workflow

Full suite runs once before commit. Successful completion commits and pushes directly to `origin/main`.

## Next

Phase 118B — Implement Manual Refresh Attempt Counter.
