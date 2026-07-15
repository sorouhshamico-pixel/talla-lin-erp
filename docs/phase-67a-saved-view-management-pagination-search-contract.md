# Phase 67A — Saved View Management Pagination And Search Contract

## Baseline

- Previous phase: Phase 66F clean
- Commit: 9725524
- Previous tests: 1254 passed / 11086 assertions

## Purpose

Prepare the next saved view management scalability phase.

This phase is an audit contract only. It documents that the management page currently lists all authenticated-user saved views in one table without search, report filtering, or pagination.

## Current state

- `ReportSavedViewController@index` calls `ReportSavedViewService::list($request->user())`.
- `ReportSavedViewService::list()` returns a collection from `get()`.
- The controller maps the full collection before rendering.
- The index view renders one table and has no search form.
- The index view has no report key filter.
- The index view has no pagination links.

## Scalability risk

Severity: medium.

Saved view management currently loads and renders the complete authenticated-user saved view collection. This is acceptable for small datasets, but larger saved view collections need search, filtering, and pagination.

## Phase 67B recommendations

- Add a search input for saved view name, report label, report key, and common filter display values.
- Add report key filtering using `ReportSavedViewRegistry` labels.
- Use Laravel pagination instead of eager-loading every saved view record.
- Preserve Phase 66 behavior: registry alignment, read-only filters, ownership guards, and grouped row actions.
- Keep query parameters across pagination links.
