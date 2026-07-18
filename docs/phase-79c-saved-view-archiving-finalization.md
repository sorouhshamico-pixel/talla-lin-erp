# Phase 79C — Finalize Saved View Archiving

## Baseline

- Phase 79B.
- Stable commit: `0e51cea`.
- Full suite:
  `1613 passed / 14831 assertions`.
- Phase 79B migration applied locally.
- Workflow: direct `main` only.

## Finalized database and model contract

The `report_saved_views` table contains nullable `archived_at` with a
composite user/status index.

Existing records remain active. The model exposes the datetime cast and
active/archived helpers.

## Finalized management contract

The management page supports active, archived, and all modes. Active remains
the default.

Status is retained in pagination, return queries, import preview context, and
filtered CSV export.

## Finalized lifecycle contract

Single and bulk archive/restore operations are authenticated-user scoped.

Archiving clears default status atomically. Restoring never recreates default
status. Repeated archive or restore requests are idempotent.

## Finalized action boundary

Archived saved views cannot be applied, edited, duplicated, updated, or made
default. They can be restored, permanently deleted, or explicitly exported
from management selection.

## CSV boundary

The CSV schema and version remain unchanged. `archived_at` is not exported.
The final writer and parser remain unchanged. Import creates active rows.

## Runtime scope

Phase 79C changes no runtime, migration, or database file.

It adds finalization documentation and tests and stabilizes the historical
main-only workflow assertion so it does not depend on a particular current
phase name.

## Next phase

Phase 80A — Select Next Saved View Management Contract.

Workflow: direct `main` only.
