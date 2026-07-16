# Phase 78A — Prepare Selected Saved View CSV Export Contract

## Selection decision

The next saved-view management capability is selected-row CSV export.

The management page already provides row checkboxes, select-all behavior,
selected-count feedback, and bulk deletion. The existing CSV link exports all
rows matching the current search and report filters. The smallest coherent
extension is therefore exporting only the rows selected on the current page.

## Baseline

- Phase 77C.
- Stable commit: `4220470`.
- Full suite:
  `1573 passed / 14410 assertions`.

## Phase 78A boundary

This phase is contract-only:

- no production runtime file changes;
- no route changes;
- no database changes;
- no view behavior changes;
- no writer changes.

## Phase 78B HTTP contract

Phase 78B will add an authenticated POST route:

```text
POST /reports/saved-views/export-selected
reports.saved-views.export-selected
```

The request accepts:

```text
saved_view_ids: required array, minimum one item
saved_view_ids.*: integer and distinct
```

## User-scope contract

Only saved views owned by the authenticated user may be exported.

Foreign-user IDs and nonexistent IDs are ignored without revealing whether
they exist. A valid request that resolves to zero owned rows produces a valid
header-only CSV.

Explicit selected IDs are not constrained by the current search or report
filter query. Selection itself defines the requested set.

Rows use deterministic management ordering:

1. default rows first;
2. name ascending;
3. id ascending.

## CSV response contract

Phase 78B reuses `ReportSavedViewCsvExportWriter` without changing it.

The response preserves:

- UTF-8 BOM;
- registry-owned header;
- registry current version;
- human-readable summary;
- machine-readable payload;
- import round trip;
- `text/csv; charset=UTF-8`.

Filename:

```text
saved-views-selected-YYYYMMDD-HHMMSS.csv
```

## Management-page contract

The existing checkboxes, select-all control, and selected-count JavaScript are
reused.

The bulk-selection form becomes a POST form whose default action exports the
selected rows. The existing delete button uses its own `formaction` and
submits `_method=DELETE` only when delete is clicked.

The new button label is:

```text
تصدير المحدد CSV
```

Both selected actions remain disabled when no row is selected.

## Preserved behavior

The existing filtered export, bulk deletion, delete-all action, import preview,
import apply flow, and writer ownership boundary remain unchanged.

## Next phase

Phase 78B — Implement Selected Saved View CSV Export.
