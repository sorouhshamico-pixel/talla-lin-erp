# Phase 74A — Saved View Import Export Version Registry Contract

## Baseline

- Phase 73C clean.
- Commit: `6a38b8c`.
- Confirmed full suite: `1454 passed / 12981 assertions`.

## Scope

This is an audit and contract phase only. It does not modify runtime implementation.

## Current state

The saved-view controller currently owns:

- the `format_version` column name;
- the current explicit version;
- the supported versions list;
- the legacy required columns;
- the explicit version 1 required columns.

Phase 73 finalized the behavior, but the format metadata remains embedded in the controller. A dedicated registry is needed before another version is introduced.

## Proposed registry

```text
App\Support\Reports\ReportSavedViewImportExportVersionRegistry
```

File:

```text
app/Support/Reports/ReportSavedViewImportExportVersionRegistry.php
```

The class should be final, static, deterministic, and metadata-only. It must not access database, HTTP, session, filesystem, or authentication state.

## Required public API

```php
formatVersionColumn(): string
currentVersion(): string
supportedVersions(): array
supports(string $version): bool
legacyRequiredColumns(): array
requiredColumns(string $version): array
exportHeader(): array
requiresFiltersPayload(string $version): bool
```

## Registry metadata

Legacy unversioned required columns:

```text
name,report_label,report_key,is_default,filter_count,filters_summary,updated_at
```

Explicit version 1 required columns and export header:

```text
format_version,name,report_label,report_key,is_default,filter_count,filters_summary,filters_payload,updated_at
```

Version 1 requires `filters_payload`.

Unsupported versions return an empty array from `requiredColumns()` and `false` from `requiresFiltersPayload()`.

## Phase 74B migration

Phase 74B should:

- create the registry;
- remove the four inline format metadata constants from the controller;
- replace them with registry calls;
- preserve the complete Phase 73 behavior without changing routes, views, models, service behavior, or database state.

## Preserved behavior

- `format_version` remains the first export column.
- New exports remain version `1`.
- Explicit version 1 requires a non-empty JSON-object `filters_payload`.
- Empty, unsupported, or mixed explicit versions block all writes.
- Legacy unversioned files remain supported.
- `filters_summary` remains human-readable only.
- Import apply remains transaction-protected and user-scoped.
- Duplicates remain skipped without overwrite.

## Next phase

Phase 74B — Implement Saved View Import Export Version Registry.
