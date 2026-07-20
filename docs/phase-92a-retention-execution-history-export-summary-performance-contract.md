# Phase 92A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Performance Contract

## Baseline

- Phase: Phase 91C
- Commit: `fec63654486ed17d5163aa42ab7d9bd02c2bd9ec`
- Full suite: 1877 passed
- Assertions: 17079
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 92A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Protect the filtered retention execution-history summary from query-count, memory, and rendering regressions.

## Query budget

A summary call is limited to one aggregate query.

It must load zero execution rows and must not perform:

- Pagination queries
- Export-limit count queries
- N+1 queries
- Chunking
- Cursor iteration

## Aggregation

Counts, sums, average, minimum timestamp, and maximum timestamp remain database-side.

Collection-based aggregation in PHP is forbidden.

The summary result size remains constant regardless of matching row count.

## Filtering

The implementation must continue using the shared filter application for:

- `type`
- `status`
- `actor_user_id`
- `started_from`
- `started_to`

Validation and inclusive date boundaries remain unchanged.

## Database

No new table, column, migration, or index is required.

Existing indexes must be reused.

## Controller and View

The summary is computed once for an HTML request.

It is not computed for the JSON status response.

The View receives a plain summary array and performs no queries or client-side aggregation.

## Limits

CSV and JSON export limits do not apply to the summary.

The summary timeout contract remains 30 seconds.

## Observability

Summary rendering creates no audit row, sharing activity, or export log entry.

Sensitive filters are not newly logged.

## Planned implementation

Phase 92B may add defensive assertions to the existing summary Service and focused performance tests.

It must not change the Controller, View, Routes, database, or migrations.

## Compatibility

Summary payload, filters, empty state, administration HTML and JSON, exports, limits, presets, date shortcuts, history schema, and history Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 92B — Implement Saved View Sharing Activity Retention Execution History Export Summary Performance Guards.
