# Phase 73C — Saved View Import Export Format Version Finalization

## Baseline

- Phase 73B clean.
- Commit: `0bb324d`.
- Confirmed full suite: `1444 passed / 12866 assertions`.

## Scope

This is a finalization phase only. Runtime implementation files remain locked.

## Finalized export schema

```text
format_version,name,report_label,report_key,is_default,filter_count,filters_summary,filters_payload,updated_at
```

`format_version` is the first column, and every new export uses version `1`.

## Finalized explicit version 1 rules

- `filters_payload` is required.
- `filters_payload` must be non-empty.
- `filters_payload` must decode to a JSON object.
- Empty, unsupported, or mixed explicit versions reject the complete file before writes.
- The importer does not infer a version from any other column.

## Finalized legacy compatibility

A file without the `format_version` header remains a legacy unversioned file.

- Legacy files without `filters_payload` remain supported and import empty filters.
- Legacy files with `filters_payload` remain supported and import validated structured filters.

## Preserved safety behavior

- `filters_summary` remains human-readable only.
- `filters_payload` remains the sole machine-readable filters source.
- Import apply revalidates the payload before writes.
- Import apply remains protected by a database transaction.
- Imported rows remain scoped to the authenticated user.
- Duplicates remain skipped without overwriting existing rows.
- Default normalization remains per user and report.
- Preview, export, bulk selection, and pagination remain stable.

## Next phase

Phase 74A — Saved View Import Export Version Registry Contract.
