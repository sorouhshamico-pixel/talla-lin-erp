# Phase 85C — Finalize Saved View Sharing Activity Retention Administration

## Baseline

- Phase 85B
- Commit `fd7fbe3`
- Full suite: 1780 passed
- Assertions: 16047
- Working tree clean
- One registered worktree

## Finalized capability

Saved View Sharing Activity Retention Administration is complete.

The implementation includes:

- explicit authorization ability
- protected administration routes
- HTML status page
- JSON status response
- manual preview
- guarded manual execution
- concurrency lock
- audit logging
- cached last-preview and last-execution results

## Authorization

Ability:

`manage_saved_view_share_activity_retention`

The ability is registered in `AppServiceProvider`.

The current rule grants access to users for whom `User::isOwner()` returns true.

All routes require:

- `auth`
- `can:manage_saved_view_share_activity_retention`

## Status interface

The administration interface exposes:

- retention enabled state
- retention days
- chunk size
- schedule
- candidate count
- oldest activity timestamp
- newest activity timestamp
- last manual preview
- last manual execution

Configuration remains read-only in the web interface.

## Manual preview

Manual preview:

- does not delete rows
- accepts 30 to 3650 days
- records actor user ID
- records requested days
- stores the latest preview result
- writes an application log entry

## Manual execution

Manual execution:

- requires the confirmation token `PRUNE`
- accepts 30 to 3650 days
- accepts chunk size from 1 to 10000
- records actor user ID
- records requested days
- records requested chunk size
- stores the latest execution result
- writes an application log entry

## Concurrency

Lock:

`saved-view-share-activity-retention-prune`

Lock lifetime:

3600 seconds

A lock conflict returns HTTP 409.

Overlapping retention administration execution is forbidden.

## Responses

- success: 200
- forbidden: 403
- validation error: 422
- lock conflict: 409

## Compatibility boundaries

Phase 85 does not change:

- retention service behavior
- retention command behavior
- scheduler behavior
- activity schema
- activity export
- sharing permissions
- saved-view CSV
- saved-view format version

## Phase 85C scope

Phase 85C changes documentation and tests only.

It introduces no runtime, schema, migration, route, controller, service,
view, or provider changes.

## Workflow policy

- Work directly on `main`.
- Run the full suite once before commit.
- Do not run the full suite after commit.
- Push every successful phase immediately.
- Use the pushed commit as the next baseline.

## Next recommendation

Phase 86A — Prepare Saved View Sharing Activity Retention Execution History Contract.
