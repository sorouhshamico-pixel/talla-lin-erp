# Phase 71B — Saved View Import Apply Implementation

## Baseline

- Previous phase: Phase 71A clean
- Commit: 7dbfea6
- Previous tests: 1380 passed / 12330 assertions

## Purpose

Implement a controlled write-capable import apply workflow for saved view management.

## Implemented behavior

- Adds a dedicated authenticated import apply route.
- Adds an import apply controller action.
- Adds an apply form after a fully valid preview.
- Revalidates the CSV payload before any writes.
- Rejects invalid payloads without writing.
- Uses a database transaction for apply.
- Creates saved views only for the authenticated user.
- Skips duplicate `user_id + report_key + name` rows.
- Does not overwrite existing saved views.
- Normalizes default saved views so only one default exists per report per user.
- Creates imported saved views with empty filters.

## Duplicate policy

Existing saved views with the same authenticated user, `report_key`, and `name` are skipped.

## Filters policy

`filters_summary` is not machine-readable. Imported saved views are created with empty filters until a dedicated filters payload contract exists.

## Preserved behavior

- Phase 70 import preview remains available.
- Phase 69 CSV export remains stable.
- Phase 68 bulk selection remains stable.
- Phase 67 pagination remains stable.

## Guardrails

- Apply import must re-run preview validation before any writes.
- Apply import must not write when headers or rows are invalid.
- Apply import must run inside a database transaction.
- Apply import must skip duplicates and must not overwrite existing saved views.
- Apply import must create saved views only for the authenticated user.
- Apply import must not use `filters_summary` as machine-readable filters.
- Apply import must preserve CSV export, preview, bulk selection, and pagination.
