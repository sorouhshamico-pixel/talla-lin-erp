# Phase 77A — Saved View CSV Export Writer Contract

## Baseline

- Phase 76C clean.
- Commit: `05bee93`.
- Confirmed full suite: `1546 passed / 14030 assertions`.

## Scope

This is an audit and contract phase only. No runtime implementation changes
are allowed.

## Current export boundary

`ReportSavedViewController::export()` currently owns the streamed response
and the complete CSV closure body.

The closure currently:

- opens `php://output`;
- writes the UTF-8 BOM;
- writes the version-registry header;
- formats each filter summary;
- encodes the machine filter payload;
- writes the current format version and all CSV columns;
- closes the stream.

## Proposed writer

```text
App\Support\Reports\ReportSavedViewCsvExportWriter
```

File:

```text
app/Support/Reports/ReportSavedViewCsvExportWriter.php
```

The writer should be final, stateless, and constructor-free.

## Public API

```php
public function write(iterable $formattedSavedViews): void
```

The writer receives objects already produced by
`ReportSavedViewController::formatSavedView()`.

It must not receive models, requests, responses, users, query services, or
database access.

## Formatted input

Each formatted object supplies:

```text
name
report_label
report_key
is_default
filters
updated_at
```

Each filter item supplies:

```text
key
label
value
display_value
```

The writer uses the human label and display value only for
`filters_summary`. It uses the key and original value only for
`filters_payload`.

## Output stream

The writer owns:

- opening `php://output` in write mode;
- graceful return when opening fails;
- UTF-8 BOM;
- registry-owned header;
- one CSV row per input item;
- closing the stream.

The writer preserves the input order and performs no sorting.

## CSV columns

```text
format_version
name
report_label
report_key
is_default
filter_count
filters_summary
filters_payload
updated_at
```

The registry remains the sole source of the header and current format
version.

## Filter summary

Human-readable form:

```text
label: display_value
label: display_value (raw_value)
```

The raw suffix is used only when the raw value is non-empty and differs from
the display value. Multiple filters are joined with `; `.

## Filter payload

The payload is a JSON object reconstructed from each filter's machine key
and original value.

Encoding flags remain:

```text
JSON_UNESCAPED_UNICODE
JSON_UNESCAPED_SLASHES
```

Empty filters and encoding failures produce `{}`.

## Controller boundary after Phase 77B

The controller retains:

- request validation;
- active search and report filters;
- user-scoped export query;
- formatting models into export objects;
- filename;
- streamed response;
- content type.

The writer owns the complete CSV byte-writing closure body.

## Next phase

Phase 77B — Implement Saved View CSV Export Writer.
