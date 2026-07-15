# Phase 68C — Saved View Management Bulk Selection UX Cleanup

## Baseline

- Previous phase: Phase 68B clean
- Commit: a9277d9
- Previous tests: 1301 passed / 11472 assertions

## Purpose

Clean the saved view management Blade markup after Phase 68B and improve bulk selection usability.

## Implemented behavior

- Removes accidental script-artifact text from `resources/views/reports/saved-views/index.blade.php`.
- Keeps the bulk delete form and row selection controls from Phase 68B.
- Disables the bulk delete button until at least one row is selected.
- Adds a visible selected-count label.
- Updates the selected count when a row checkbox changes.
- Updates the selected count when select-all changes.
- Supports select-all indeterminate state when only some rows are selected.
- Guards against empty form submission.
- Preserves the delete confirmation for selected bulk deletion.

## Guardrails

- Do not change bulk delete controller behavior.
- Do not change routes.
- Do not change saved view service pagination behavior.
- Do not change Phase 67 search, report filter, per-page, pagination, active-filter, empty-state, or result-summary behavior.
- Do not remove existing row actions or delete-all.
