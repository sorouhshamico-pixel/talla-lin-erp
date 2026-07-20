# Phase 86A — Prepare Saved View Sharing Activity Retention Execution History Contract

## Baseline

- Phase 85C
- Commit `9a3e59e`
- Full suite: 1786 passed
- Assertions: 16115
- Working tree clean
- One registered worktree

## Scope

Phase 86A is contract-only.

It introduces no runtime, database, migration, model, service, controller,
route, or view changes.

## Purpose

Persist retention preview and execution outcomes for operational traceability.

## Planned table

`report_saved_view_share_activity_retention_executions`

## Planned model

`App\Models\ReportSavedViewShareActivityRetentionExecution`

## Record types

- manual preview
- manual execution
- scheduled execution
- command execution

## Statuses

- succeeded
- failed
- conflicted

## Core fields

The execution history row stores:

- operation type
- status
- optional actor user ID
- requested retention days
- optional chunk size
- candidate count
- deleted count
- cutoff timestamp
- duration
- failure class
- bounded failure message
- restricted context
- start and finish timestamps

## Foreign key behavior

`actor_user_id` is nullable.

Deleting a user sets the reference to null.

## Immutability

Rows are append-only.

The implementation inserts a completed final row.

Normal updates and deletes are forbidden.

## Safety constraints

History context must not include:

- full activity metadata
- filters payload
- credentials
- secrets

Failure messages are limited to 2000 characters.

## Recording requirements

History must cover:

- manual preview
- manual execution
- scheduled execution
- command execution
- lock conflict
- operation failure

Recording retention execution history must not create sharing activity rows.

## Failure handling

A history-write failure must not hide the primary operation failure.

The primary operation result remains authoritative.

History-write failures are logged separately.

## Read interface

Access requires:

`manage_saved_view_share_activity_retention`

The interface requires pagination.

Defaults:

- page size: 25
- maximum page size: 100
- ordering: `created_at desc`, then `id desc`

Filters:

- type
- status
- actor user ID
- started from
- started to

## Export

Execution-history export is outside Phase 86.

It may be added in a future phase.

## Compatibility boundaries

Phase 86 must not change:

- retention policy semantics
- retention administration contract
- retention command signature
- scheduler contract
- sharing activity schema
- sharing activity export
- sharing permissions

## Next phase

Phase 86B — Implement Saved View Sharing Activity Retention Execution History.
