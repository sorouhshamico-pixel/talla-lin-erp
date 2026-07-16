# Phase 76C — Saved View Import Apply Service Finalization

## Baseline

- Phase 76B clean.
- Commit: `d4f1b5c`.
- Confirmed full suite: `1536 passed / 13917 assertions`.

## Scope

This is a finalization phase only. No runtime implementation changes are
allowed.

## Finalized service

```text
App\Services\ReportSavedViewImportApplyService
```

The service is final, stateless, and constructor-free.

Its only public API is:

```php
public function apply(User $user, array $rows): array
```

Return shape:

```text
created: int
skipped: int
```

## Finalized transaction boundary

The complete row set is processed inside one `DB::transaction()` call.

Exceptions roll back all writes. Partial commits are not allowed.

## Finalized row policy

- rows whose status is not `valid` are ignored;
- ignored rows do not increment either result counter;
- filters come from parser-cleaned row data;
- missing filters default to an empty array.

## Finalized duplicate policy

A duplicate is an exact existing match on:

```text
user_id
report_key
name
```

Duplicates are skipped without update or overwrite. They increment
`skipped` only.

## Finalized default policy

A new row is default only when `is_default` equals Arabic `نعم`.

Before creating a new default, existing defaults are cleared only for the
same user and report. Other users and other reports remain unchanged. If
multiple new defaults for the same report occur in one batch, the last one
wins.

## Finalized controller boundary

The controller retains:

- request validation;
- base64 decoding;
- temporary-file lifecycle;
- apply-time parser revalidation;
- invalid-file blocking;
- redirects;
- exact Arabic status messages.

The controller delegates valid parser rows to the import-apply service. The
former private `applySavedViewImportRows()` helper remains absent.

## Preserved behavior

- authentication and user scope;
- legacy and versioned import;
- machine-readable `filters_payload`;
- display-only `filters_summary`;
- duplicate skipping;
- default normalization;
- exact created and skipped counts;
- exact success and failure messages;
- preview, export, bulk selection, and pagination behavior.

## Next phase

Phase 77A — Saved View CSV Export Writer Contract.

The next contract should audit extraction of the CSV stream-writing block
from the controller while preserving UTF-8 BOM, registry-owned header and
format version, human-readable filter summary, machine-readable filter
payload, row ordering, file name, content type, and streamed response
behavior.
