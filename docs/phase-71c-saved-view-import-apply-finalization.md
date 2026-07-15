# Phase 71C — Saved View Import Apply Finalization

## Baseline

- Previous phase: Phase 71B clean
- Commit: 7f35cea
- Previous tests: 1389 passed / 12396 assertions

## Purpose

Finalize the saved view import apply workflow.

This phase is documentation and finalization testing only. It must not change implementation files.

## Finalized behavior

- Import apply route is locked.
- Import apply controller action is locked.
- Import apply form is locked.
- Import apply requires authentication.
- Import apply revalidates payloads before writes.
- Invalid payloads do not write rows.
- Import apply uses a database transaction.
- Imported saved views are scoped to the authenticated user.
- Duplicate `user_id + report_key + name` rows are skipped.
- Existing saved views are not overwritten.
- `is_default` is normalized per user/report.
- Imported saved views use empty filters.
- Phase 70 preview remains available.
- Phase 69 CSV export remains stable.
- Phase 68 bulk selection remains stable.
- Phase 67 pagination remains stable.

## Duplicate policy

Existing saved views with the same authenticated user, `report_key`, and `name` are skipped.

## Filters policy

`filters_summary` remains human-readable only. Imported saved views use empty filters until a dedicated `filters_payload` contract is implemented.

## Next recommendation

Phase 72A — Saved View Machine-Readable Filters Payload Contract.

Import apply is now stable, but imported rows intentionally use empty filters because `filters_summary` is not machine-readable. The next safe step is a contract for `filters_payload` before lossless import/export.

## Guardrails

- Do not modify Phase 71B implementation in this finalization phase.
- Import apply must remain transaction-protected.
- Import apply must revalidate payloads before writes.
- Invalid payloads must not write rows.
- Duplicate saved views must be skipped, not overwritten.
- Imported saved views must remain scoped to the authenticated user.
- `filters_summary` must not be treated as machine-readable filters.
- Keep preview, export, bulk selection, and pagination stable.
