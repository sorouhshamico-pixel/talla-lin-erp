# Phase 84A — Prepare Saved View Sharing Activity Retention Policy Contract

## Baseline

- Phase 83C
- Commit `b7903cd`
- Full suite: 1745 passed
- Assertions: 15798
- Working tree clean
- One registered worktree

## Scope

Phase 84A is contract-only.

It must not change:

- runtime behavior
- database schema
- migrations
- routes
- controllers
- services
- console commands
- scheduler registration
- activity export
- sharing permissions
- saved-view CSV format
- saved-view format version

## Default policy

The default retention mode is `retain_forever`.

Automatic pruning is disabled by default.

No retention period is applied unless pruning is explicitly enabled.

## Optional pruning policy

When explicitly configured, pruning must support:

- retention periods from 30 to 3650 days
- dry-run mode
- manual command execution
- conditional scheduled execution
- chunked deletion
- deterministic cutoff calculation

A single unbounded mass delete is forbidden.

## Eligibility

A row is eligible when its `created_at` value is older than the cutoff.

The cutoff is exclusive.

Rows exactly at the cutoff are retained.

Future-dated rows must never be deleted.

## Scope and safety

Pruning is a global table policy.

The command must not accept:

- owner user scope
- recipient user scope
- activity action filters

Each deletion chunk must execute in its own transaction.

A failure stops further processing. Previously committed chunks may remain deleted.

## Observability

Dry-run output must include the candidate count.

Execution output must include:

- deleted count
- cutoff
- duration

Execution is logged through the default application log.

Pruning must not create new sharing activity rows.

## Immutability boundary

Normal model updates and deletes remain forbidden.

The retention service may delete eligible rows through the query builder.

This is the only policy-defined exception to activity immutability.

## Configuration contract

Required configuration keys:

- `reports.saved_view_share_activity_retention.enabled`
- `reports.saved_view_share_activity_retention.days`
- `reports.saved_view_share_activity_retention.chunk_size`
- `reports.saved_view_share_activity_retention.schedule`

Defaults:

- enabled: `false`
- days: `null`
- chunk size: `500`
- schedule: `daily`

## Planned implementation

Phase 84B should add:

- `ReportSavedViewShareActivityRetentionService`
- `reports:prune-saved-view-share-activities`
- reports retention configuration
- conditional scheduler registration
- implementation tests

## Compatibility boundaries

Phase 84 must not alter:

- activity actions
- activity exports
- sharing permissions
- saved-view CSV
- saved-view format version
- database schema

## Workflow policy

- Work directly on `main`.
- Run the full suite once before commit.
- Do not repeat the full suite after commit.
- Push each successful phase immediately to `origin/main`.
- Use the pushed commit as the next baseline.
