# Phase 75A — Saved View CSV Import Parser Contract

## Baseline

- Phase 74C clean.
- Commit: `ce63519`.
- Confirmed full suite: `1483 passed / 13297 assertions`.

## Scope

This is an audit and contract phase only. No runtime implementation changes are allowed.

## Current state

The saved-view controller currently performs all CSV parsing work:

- opening and reading the CSV file;
- BOM normalization;
- legacy versus explicit-version detection;
- required-column validation;
- row iteration;
- row-level validation;
- `filters_payload` JSON decoding;
- recursive filter cleaning;
- mixed-version detection;
- preview summary counting.

Database application is already separate through `applySavedViewImportRows()`.

## Proposed parser

```text
App\Support\Reports\ReportSavedViewCsvImportParser
```

File:

```text
app/Support/Reports/ReportSavedViewCsvImportParser.php
```

The parser should be final, stateless, and read-only. It may read the supplied CSV path but must not access database, HTTP, session, authentication, routes, views, or Eloquent models.

## Public API

```php
public function parse(string $path): array
```

The result shape remains:

```text
headers
header_errors
rows
total_rows
valid_rows
invalid_rows
```

Each parsed row retains:

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

## Dependencies

The parser may use only:

```text
ReportSavedViewImportExportVersionRegistry
ReportSavedViewRegistry
```

The version registry remains the source of format-version columns, supported versions, schemas, and payload requirements.

The report registry remains the source of valid report keys and canonical labels.

## Preserved validation

All current Arabic errors remain exact, including:

```text
تعذر قراءة ملف CSV.
ملف CSV فارغ أو غير صالح.
الأعمدة المطلوبة غير موجودة:
قيمة format_version مطلوبة.
إصدار تنسيق ملف الاستيراد غير مدعوم.
filters_payload مطلوب في الإصدار 1.
اسم العرض مطلوب.
اسم العرض يتجاوز 120 حرفًا.
مفتاح التقرير مطلوب.
مفتاح التقرير غير معروف.
قيمة الافتراضي غير صالحة.
عدد الفلاتر يجب أن يكون رقمًا صحيحًا.
filters_payload يجب أن يكون JSON object صالحًا.
يحتوي الملف على أكثر من إصدار format_version.
```

`filters_summary` remains display-only and is never parsed.

## Phase 75B migration

Phase 75B should:

- create the parser;
- inject it into `ReportSavedViewController`;
- replace both private parsing calls with `parse()`;
- remove the four parsing helper methods from the controller;
- retain `previewImport()`, `applyImport()`, and `applySavedViewImportRows()`;
- keep transaction, user scope, duplicate handling, and default normalization in the controller;
- make no route, view, service, model, migration, or database change.

## Next phase

Phase 75B — Implement Saved View CSV Import Parser.
