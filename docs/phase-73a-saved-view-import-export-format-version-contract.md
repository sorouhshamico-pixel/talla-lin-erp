# Phase 73A — Saved View Import Export Format Version Contract

## Baseline

- Phase 72C clean.
- Commit: `5941826`.
- Confirmed full suite: `1423 passed / 12717 assertions`.

## Scope

This is an audit and contract phase only. It does not modify CSV export, import preview, import apply, routes, services, views, models, or the saved-view registry.

## Current state

Saved-view CSV import and export now support lossless structured filters through `filters_payload`. The format is still unversioned, so future column or validation changes cannot be interpreted deterministically.

## Proposed explicit version contract

The next implementation phase should add `format_version` as the first CSV column.

The first explicit version is `1`.

Every newly exported row should contain:

```text
format_version=1
```

Version detection must use only the explicit `format_version` header and its exact row value. The importer must not infer a version from `filters_payload`, column count, or any other field.

## Compatibility rules

### Legacy unversioned files

Files without the `format_version` column remain legacy unversioned files.

- Legacy files without `filters_payload` remain accepted and import empty filters.
- Legacy files with `filters_payload` remain accepted and use validated payload filters.

### Explicit version 1 files

Version 1 requires these columns:

```text
format_version,name,report_label,report_key,is_default,filter_count,filters_summary,filters_payload,updated_at
```

`format_version` must equal `1` on every data row.

`filters_payload` is required and must decode to a JSON object.

### Rejected files

The importer must reject the complete file before writes when:

- the explicit version is empty or malformed;
- the explicit version is unsupported;
- one file contains mixed explicit versions;
- an explicit version 1 file omits `filters_payload`;
- `filters_payload` is invalid JSON or a JSON list.

## Preserved behavior

- `filters_summary` remains human-readable only.
- `filters_payload` remains the sole machine-readable filters source.
- Legacy CSV compatibility remains intact.
- Import apply continues to revalidate before writes.
- Import apply remains inside a database transaction.
- Imported records remain scoped to the authenticated user.
- Duplicates remain skipped without overwrite.
- Default normalization remains per user and report.
- Preview, export, bulk selection, and pagination remain stable.

## Next phase

Phase 73B — Implement Saved View Import Export Format Version.
