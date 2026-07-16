# Phase 74C — Saved View Import Export Version Registry Finalization

## Baseline

- Phase 74B clean.
- Commit: `18aa201`.
- Confirmed full suite: `1473 passed / 13188 assertions`.

## Scope

This is a finalization phase only. No registry, controller, route, service, view, model, or existing registry changes are allowed.

## Finalized registry

```text
App\Support\Reports\ReportSavedViewImportExportVersionRegistry
```

The registry is:

- final;
- non-instantiable through a private constructor;
- static and deterministic;
- metadata-only;
- independent from database, HTTP, session, filesystem, and authentication state.

## Finalized public API

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

The current version is the string `1`, and `supportedVersions()` returns:

```php
['1']
```

## Finalized schemas

Legacy unversioned required columns:

```text
name,report_label,report_key,is_default,filter_count,filters_summary,updated_at
```

Version 1 and export header:

```text
format_version,name,report_label,report_key,is_default,filter_count,filters_summary,filters_payload,updated_at
```

## Finalized controller integration

The controller delegates to the registry for:

- export header;
- current version;
- format-version column name;
- legacy columns;
- version-specific required columns;
- supported-version checks;
- `filters_payload` requirements.

The previous inline version metadata constants remain removed.

## Preserved behavior

- Versioned export/import remains lossless.
- Explicit V1 continues to require a non-empty JSON-object `filters_payload`.
- Invalid, unsupported, and mixed versions continue to reject the complete file before writes.
- Legacy CSV files with and without `filters_payload` remain supported.
- `filters_summary` remains human-readable only.
- Transaction, authenticated-user scope, duplicate skipping, default normalization, preview, export, bulk selection, and pagination remain stable.

## Next phase

Phase 75A — Saved View CSV Import Parser Contract.
