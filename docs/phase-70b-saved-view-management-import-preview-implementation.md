# Phase 70B — Saved View Management Import Preview Implementation

## Baseline

- Previous phase: Phase 70A clean
- Commit: 99f4022
- Previous tests: 1355 passed / 12043 assertions

## Purpose

Implement a preview-only CSV import workflow for saved view management.

This phase intentionally does not create or update saved views. It only parses CSV input, validates headers and rows, and displays a preview.

## Implemented behavior

- Adds an authenticated import preview route.
- Adds a controller action for preview-only CSV parsing.
- Adds an import preview form to the saved view management page.
- Validates required CSV headers.
- Displays row-level validation results.
- Rejects unknown `report_key` values per row.
- Displays preview summary counts.
- Keeps preview read-only.
- Preserves Phase 69 CSV export.
- Preserves Phase 68 bulk selection.
- Preserves Phase 67 pagination.

## Required CSV columns

- `name`
- `report_label`
- `report_key`
- `is_default`
- `filter_count`
- `filters_summary`
- `updated_at`

## Guardrails

- Import preview must not create or update `report_saved_views` rows.
- Import preview must require authentication.
- Import preview must validate headers before row validation.
- Import preview must reject unknown `report_key` values per row.
- Import preview must not silently overwrite existing saved views.
- Import preview must not change Phase 69 export behavior.
- Import preview must not change Phase 68 bulk selection behavior.
- Import preview must not change Phase 67 pagination behavior.
