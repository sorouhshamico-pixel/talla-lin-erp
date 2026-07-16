# Phase 75C — Saved View CSV Import Parser Finalization

## Baseline

- Phase 75B clean.
- Commit: `c09f1d9`.
- Confirmed full suite: `1504 passed / 13560 assertions`.

## Scope

This is a finalization phase only. No runtime implementation changes are
allowed.

## Finalized parser

```text
App\Support\Reports\ReportSavedViewCsvImportParser
```

The parser is final, stateless, and constructor-free. Its only public API is:

```php
public function parse(string $path): array
```

It may read the supplied CSV path but must not access database, Eloquent,
request, response, session, authentication, redirects, views, or routes.

## Finalized responsibilities

The parser owns:

- read-only CSV opening;
- UTF-8 BOM removal;
- legacy and explicit-version schema resolution;
- required-column validation;
- empty-row skipping;
- row numbering;
- row validation;
- explicit version validation;
- `filters_payload` JSON-object decoding;
- recursive imported-filter cleaning;
- mixed-version detection;
- preview result counting.

## Finalized result shape

```text
headers
header_errors
rows
total_rows
valid_rows
invalid_rows
```

Each row retains:

```text
row_number
format_version
name
report_label
report_key
is_default
filter_count
filters_summary
filters_payload
filters
status
errors
```

## Controller boundary

The controller injects the parser and uses it for both preview and apply.

The controller retains:

- request validation;
- base64 payload handling;
- temporary-file lifecycle;
- apply-time reparse;
- database transaction;
- authenticated-user scope;
- duplicate skipping;
- default normalization;
- saved-view creation.

The former inline parser helper methods remain removed.

## Preserved behavior

- preview remains read-only;
- apply reparses before writes;
- invalid headers or rows block all writes;
- legacy CSV files remain supported;
- explicit V1 round-trip remains lossless;
- `filters_summary` remains display-only;
- `filters_payload` remains the machine source;
- exact Arabic validation messages remain unchanged;
- export, bulk selection, pagination, duplicate handling, and default
  normalization remain stable.

## Next phase

Phase 76A — Saved View Import Apply Service Contract.

This next contract should audit extraction of transactional row application
from the controller while preserving authenticated-user scope, duplicate
skipping, default normalization, and record creation exactly.
