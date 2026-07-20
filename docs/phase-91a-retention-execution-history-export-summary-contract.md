# Phase 91A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Contract

## Baseline

- Phase: Phase 90C
- Commit: `02d8afc0d85e681720c09c6b9c0659a627ea9e6d`
- Full suite: 1862 passed
- Assertions: 16897
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 91A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Show an operational summary for the currently selected export filters before an operator downloads CSV or JSON.

## Filter semantics

The summary uses the existing export filters and validation:

- `type`
- `status`
- `actor_user_id`
- `started_from`
- `started_to`

Existing presets and date-range shortcuts remain compatible.

## Summary fields

- Total executions
- Succeeded executions
- Failed executions
- Conflicted executions
- Manual previews
- Manual executions
- Scheduled executions
- Command executions
- Sum of candidate counts
- Sum of deleted counts
- Average duration in milliseconds
- Oldest started timestamp
- Newest started timestamp

## Aggregation rules

Null candidate and deleted counts contribute zero to their sums.

Null durations are excluded from the average. The average is rounded to an integer.

An empty result returns zero counts and null average, oldest timestamp, and newest timestamp.

Timestamps use ISO 8601.

## Interface

Phase 91B reuses:

- The existing administration Route
- The existing administration Controller
- The existing export Service
- The existing administration View

No new Route, Controller, or Service is required.

The existing JSON status response from the administration Controller remains unchanged.

## Performance

The summary is computed server-side through aggregate queries.

Execution rows must not be loaded into PHP memory.

The summary is not constrained by CSV or JSON export row limits.

## Privacy

The summary must not render:

- Context payloads
- Failure messages
- Failure classes
- Actor profiles
- Credentials or secrets

## Planned implementation

Phase 91B may modify:

- `ReportSavedViewShareActivityRetentionExecutionHistoryExportService`
- `ReportSavedViewShareActivityRetentionAdminController`
- The existing retention administration View
- A new focused Phase 91B test

No Routes, database schema, or migrations may change.

## Compatibility

Export validation, serialization, CSV and JSON behavior, row limits, presets, date shortcuts, status JSON, history schema, history Model, and retention policy remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 91B — Implement Saved View Sharing Activity Retention Execution History Export Summary.
