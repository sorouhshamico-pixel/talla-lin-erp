# Phase 72B — Saved View Filters Payload Export And Import

Baseline: Phase 72A clean at commit `24d1caa`.

## Implemented behavior

- CSV export now includes `filters_payload` after `filters_summary`.
- `filters_payload` is a machine-readable JSON object.
- Empty filters are exported as `{}`.
- Import preview supports old CSV files without `filters_payload`.
- Old CSV files continue to import with empty filters.
- `filters_summary` remains human-readable only and is not parsed.
- Import apply uses parsed `filters_payload` as the only machine-readable source for filters.
- Invalid `filters_payload` blocks import apply before writes.
- Import apply remains inside a database transaction.
- Duplicate saved views are skipped without overwrite.
- Imported saved views are created only for the authenticated user.

## Next phase

Phase 72C — Finalize Saved View Filters Payload Import Export.
