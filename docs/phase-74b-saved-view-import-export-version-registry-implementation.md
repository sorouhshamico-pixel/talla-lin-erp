# Phase 74B — Implement Saved View Import Export Version Registry

## Baseline

- Phase 74A clean.
- Commit: `bd83a6c`.
- Confirmed full suite: `1464 passed / 13082 assertions`.

## Implementation

Created:

```text
App\Support\Reports\ReportSavedViewImportExportVersionRegistry
```

The registry is final, static, deterministic, and metadata-only. It centralizes:

- the `format_version` column name;
- the current version;
- supported versions;
- legacy required columns;
- explicit version schemas;
- the export header;
- the `filters_payload` requirement.

The saved-view controller no longer contains inline version metadata constants and now delegates these decisions to the registry.

## Preserved behavior

- `format_version` remains the first export column.
- New exports remain version `1`.
- Version 1 requires a non-empty JSON-object `filters_payload`.
- Unsupported and mixed versions remain rejected before writes.
- Legacy files with and without `filters_payload` remain supported.
- `filters_summary` remains human-readable only.
- Transaction, authenticated-user scope, duplicate skipping, default normalization, preview, export, bulk selection, and pagination remain unchanged.

## Next phase

Phase 74C — Finalize Saved View Import Export Version Registry.
