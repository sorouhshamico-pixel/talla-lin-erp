# Phase 79B — Implement Saved View Archiving

## Baseline

- Phase 79A.
- Stable commit: `399bd33`.
- Full suite:
  `1602 passed / 14693 assertions`.
- Workflow: direct `main` only.

## Database and model

A nullable `archived_at` timestamp and user/status composite index are added.

The model casts the value to datetime and exposes `isArchived()` and
`isActive()` helpers.

## Management modes

The management page supports active, archived, and all modes. Active is the
default.

Status is preserved through pagination, return queries, import preview, and
filtered CSV export.

## Lifecycle behavior

Single and selected archive/restore operations are authenticated-user scoped.

Archiving sets the timestamp and clears default status atomically. Restoring
clears the timestamp and never restores default status automatically.

## Report-facing behavior

Normal report saved-view lists and default lookup exclude archived rows.

Archived rows cannot be applied, edited, duplicated, updated, or made default.
They may be restored, permanently deleted, or explicitly exported from the
management page.

## CSV boundary

CSV header, version, writer, parser, and payload format remain unchanged.

Explicit selected export can include archived rows. Imported rows are active.

## Preserved behavior

- filtered and selected CSV export;
- import preview and apply;
- permanent single, selected, and delete-all operations;
- authenticated-user isolation;
- pagination and management search;
- direct `main` workflow.

## Next phase

Phase 79C — Finalize Saved View Archiving.
