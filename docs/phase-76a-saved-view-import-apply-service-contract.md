# Phase 76A — Saved View Import Apply Service Contract

## Baseline

- Phase 75C clean.
- Commit: `ffefb88`.
- Confirmed full suite: `1515 passed / 13686 assertions`.

## Scope

This is an audit and contract phase only. No runtime implementation changes
are allowed.

## Current state

`ReportSavedViewController::applyImport()` currently owns:

- request validation;
- base64 decoding;
- temporary-file creation and cleanup;
- parser revalidation;
- invalid-file rejection;
- success and failure redirects.

Its private `applySavedViewImportRows()` method currently owns:

- one database transaction;
- valid-row filtering;
- exact duplicate detection;
- duplicate skipping;
- per-user and per-report default normalization;
- `ReportSavedView` creation;
- created and skipped counters.

## Proposed service

```text
App\Services\ReportSavedViewImportApplyService
```

File:

```text
app/Services/ReportSavedViewImportApplyService.php
```

The service should be final, stateless, and transactional.

## Public API

```php
public function apply(User $user, array $rows): array
```

Return shape:

```text
created: int
skipped: int
```

The service receives rows produced by
`ReportSavedViewCsvImportParser::parse()` and must not receive an HTTP
request.

## Transaction and duplicate policy

The complete row set remains inside one `DB::transaction()` call.

A duplicate is an existing saved view with the same:

```text
user_id
report_key
name
```

Duplicates are skipped without update or overwrite. They increment
`skipped` and do not increment `created`.

Rows whose status is not `valid` remain ignored and do not increment either
counter.

## Default normalization

A row requests default status only when its `is_default` value is Arabic
`نعم`.

Before creating that row, existing defaults are cleared only for:

```text
same user_id
same report_key
```

Other users and other reports remain unchanged.

## Creation attributes

```text
user_id
report_key
name
filters
is_default
```

`filters` comes from the parser-cleaned row value or defaults to an empty
array.

The import does not write the CSV `updated_at` value.

## Controller boundary after Phase 76B

The controller will retain:

- request validation;
- base64 handling;
- temporary-file lifecycle;
- parser revalidation;
- invalid-file rejection;
- redirects and exact status messages.

The service will own:

- transaction;
- duplicate detection;
- default normalization;
- record creation;
- created and skipped counters.

## Next phase

Phase 76B — Implement Saved View Import Apply Service.
