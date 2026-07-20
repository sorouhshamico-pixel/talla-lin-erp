# Phase 85A — Prepare Saved View Sharing Activity Retention Administration Contract

## Baseline

- Phase 84C
- Commit `2df38da`
- Full suite: 1765 passed
- Assertions: 15941
- Working tree clean
- One registered worktree

## Scope

Phase 85A is contract-only.

It must not change:

- runtime behavior
- database schema
- migrations
- routes
- controllers
- services
- views
- retention policy
- retention command
- scheduler
- activity export

## Purpose

Provide authenticated operational visibility and guarded manual controls
for Saved View Sharing Activity retention.

## Authorization

Access requires:

- authentication
- permission `manage_saved_view_share_activity_retention`

Being an activity owner or recipient is not sufficient.

Cross-user operational visibility requires the explicit permission.

## Status interface

The administration page must show:

- whether retention is enabled
- configured retention days
- configured chunk size
- configured schedule
- current candidate count
- oldest activity timestamp
- newest activity timestamp
- last manual preview result
- last manual execution result

It must not expose:

- full activity metadata
- filters payload
- database credentials
- environment secrets

## Manual preview

Manual preview:

- never deletes rows
- requires a retention period
- accepts 30 to 3650 days
- reports candidate count
- reports cutoff
- reports duration

## Manual execution

Manual execution:

- requires confirmation
- requires the literal confirmation token `PRUNE`
- requires a retention period
- supports chunk size
- accepts chunk size from 1 to 10000
- reports deleted count
- reports cutoff
- reports duration

## Configuration visibility

Configuration is read-only in the web interface.

The interface must not:

- modify `.env`
- modify configuration files
- persist deployment configuration changes

Configuration changes remain deployment-managed.

## Audit

Manual preview and execution must be logged.

The log context includes:

- actor user ID
- requested retention days
- requested chunk size
- candidate or deleted count
- cutoff
- duration

Manual operations must not create sharing activity rows.

## Concurrency

Only one retention execution may run at a time.

Required lock:

`saved-view-share-activity-retention-prune`

Overlapping manual executions are forbidden.

Manual and scheduled execution overlap is forbidden.

A lock conflict returns HTTP 409.

## Responses

- success: 200
- forbidden: 403
- validation error: 422
- lock conflict: 409

## Planned implementation

Phase 85B should add:

- retention administration controller
- retention administration service
- authenticated and permission-protected routes
- status page
- JSON responses
- manual preview
- guarded manual execution
- concurrency lock
- implementation tests

## Compatibility boundaries

Phase 85 must not alter:

- existing retention service behavior
- retention command behavior
- scheduler behavior
- activity schema
- activity export
- sharing permissions
- saved-view CSV
- saved-view format version

## Workflow policy

- Work directly on `main`.
- Run the full suite once before commit.
- Do not repeat the full suite after commit.
- Push each successful phase immediately.
- Use the pushed commit as the next baseline.
