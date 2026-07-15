# Phase 72A — Saved View Machine-Readable Filters Payload Contract

## Baseline

- Previous phase: Phase 71C clean
- Commit: 1ae3818
- Previous tests: 1398 passed / 12494 assertions

## Purpose

Prepare the contract for lossless saved view import/export through a machine-readable `filters_payload` column.

Phase 71B implemented import apply safely, but imported saved views intentionally use empty filters because `filters_summary` is human-readable and not safe to parse as structured data. This phase is contract-only and must not change implementation files.

## Current state

- CSV export exists.
- CSV export includes human-readable `filters_summary`.
- `filters_payload` export column is absent.
- Import preview exists.
- Import apply exists.
- Import apply revalidates payload before writes.
- Import apply uses a database transaction.
- Import apply skips duplicates.
- Import apply imports empty filters only.
- `filters_summary` is not machine-readable.
- Lossless saved view import is absent.
- Phase 71 import apply is stable.
- Phase 70 import preview is stable.
- Phase 69 CSV export is stable.

## Phase 72B recommendation

Implement Saved View Filters Payload Export And Import.

Implementation targets:

- Add `filters_payload` as a machine-readable JSON column to saved view CSV export.
- Keep `filters_summary` as a human-readable column.
- Add `filters_payload` to required or supported import preview headers with backwards-compatible handling for older CSV files.
- Validate `filters_payload` as JSON before import apply writes.
- Validate that `filters_payload` decodes to an object or associative array.
- Sanitize unsupported or empty filter values consistently with `ReportSavedViewService::cleanFilters`.
- Use `filters_payload` for imported saved view filters when present and valid.
- Keep `filters_summary` ignored for machine-readable import.
- Keep duplicate skip policy from Phase 71B.
- Keep database transaction boundary from Phase 71B.
- Keep authenticated-user scoping from Phase 71B.
- Preserve CSV export, preview, apply, bulk selection, and pagination behavior.

## Proposed CSV columns

- `name`
- `report_label`
- `report_key`
- `is_default`
- `filter_count`
- `filters_summary`
- `filters_payload`
- `updated_at`

## Risk

Risk level: medium-high.

`filters_payload` enables lossless import/export and writes structured filter data. Invalid JSON or unsupported filters could corrupt saved view behavior if not validated carefully.

## Guardrails

- Do not implement `filters_payload` in this contract phase.
- Do not change CSV export in this contract phase.
- Do not change import preview or import apply in this contract phase.
- `filters_summary` must remain human-readable only.
- `filters_payload` must be JSON and machine-readable before it is used for import apply.
- Invalid `filters_payload` must block writes.
- Imported filters must remain scoped to the authenticated user.
- Duplicate saved views must still be skipped and not overwritten.
- Import apply must remain transaction-protected.
- Keep Phase 71 import apply stable.
- Keep Phase 70 import preview stable.
- Keep Phase 69 CSV export stable.
