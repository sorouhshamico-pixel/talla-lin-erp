# Phase 75B — Implement Saved View CSV Import Parser

## Baseline

- Phase 75A clean.
- Commit: `480eba8`.
- Confirmed full suite: `1495 passed / 13430 assertions`.

## Implementation

Created:

```text
App\Support\Reports\ReportSavedViewCsvImportParser
```

The parser is final and stateless. It now owns:

- read-only CSV opening and parsing;
- UTF-8 BOM header normalization;
- legacy and explicit-version schema resolution;
- required-column validation;
- row iteration and validation;
- `filters_payload` JSON-object decoding;
- recursive imported-filter cleaning;
- empty-row detection;
- mixed-version detection;
- preview result counting.

## Controller integration

`ReportSavedViewController` injects the parser and uses the same `parse()` path for both preview and apply.

The controller retains:

- request validation;
- base64 payload handling;
- temporary-file creation and cleanup;
- apply-time reparse;
- database writes;
- transaction protection;
- authenticated-user scope;
- duplicate skipping;
- default normalization.

## Preserved behavior

All Phase 70 through Phase 74 result shapes, exact Arabic validation messages, legacy compatibility, explicit V1 behavior, filter policies, export, bulk selection, and pagination remain unchanged.

## Next phase

Phase 75C — Finalize Saved View CSV Import Parser.
