# Phase 73B — Implement Saved View Import Export Format Version

## Baseline

- Phase 73A clean.
- Commit: `7a621c9`.
- Confirmed full suite: `1433 passed / 12801 assertions`.

## Implemented behavior

- CSV export now writes `format_version` as the first column.
- Every newly exported row uses explicit version `1`.
- Explicit version 1 files require the `filters_payload` column.
- Explicit version 1 rows require a non-empty JSON-object `filters_payload`.
- Empty, unsupported, and mixed explicit versions are rejected before writes.
- Version detection uses only the explicit `format_version` header and row value.
- Files without `format_version` remain legacy unversioned CSV files.
- Legacy files without `filters_payload` remain supported and import empty filters.
- Legacy files with `filters_payload` remain supported and preserve validated filters.
- `filters_summary` remains human-readable only and is never parsed.
- Transaction protection, authenticated-user scoping, duplicate skipping, default normalization, preview, export, bulk selection, and pagination remain preserved.

## Version 1 schema

```text
format_version,name,report_label,report_key,is_default,filter_count,filters_summary,filters_payload,updated_at
```

## Next phase

Phase 73C — Finalize Saved View Import Export Format Version.
