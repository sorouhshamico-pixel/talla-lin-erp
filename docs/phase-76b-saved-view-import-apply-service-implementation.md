# Phase 76B — Implement Saved View Import Apply Service

## Baseline

- Phase 76A clean.
- Commit: `6d0e2b1`.
- Confirmed full suite: `1527 passed / 13835 assertions`.

## Implementation

Created:

```text
App\Services\ReportSavedViewImportApplyService
```

The service is final, stateless, and constructor-free.

It now owns:

- one transaction around the complete row set;
- invalid-row skipping;
- exact duplicate detection by user, report key, and name;
- duplicate counting without overwrite;
- per-user and per-report default normalization;
- saved-view creation;
- created and skipped result counters.

## Controller boundary

`ReportSavedViewController` injects both the CSV parser and the import-apply
service.

The controller retains:

- request validation;
- base64 decoding;
- temporary-file lifecycle;
- parser revalidation;
- invalid-file rejection;
- redirects;
- exact Arabic success and failure messages.

The former private `applySavedViewImportRows()` helper is removed.

## Preserved behavior

- all rows are applied inside one transaction;
- invalid rows are ignored and not counted;
- duplicates are skipped without update;
- defaults are normalized only for the same user and report;
- the last new default row for a report wins;
- parser-cleaned filters are persisted unchanged;
- cross-user records and other-report defaults remain unchanged;
- result counts and exact controller messages remain stable.

## Next phase

Phase 76C — Finalize Saved View Import Apply Service.
