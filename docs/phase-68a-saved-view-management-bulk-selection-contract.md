# Phase 68A — Saved View Management Bulk Selection Contract

## Baseline

- Previous phase: Phase 67E clean
- Commit: c54365e
- Previous tests: 1288 passed / 11360 assertions

## Purpose

Prepare the contract for the next saved view management improvement: selective bulk actions.

Phase 67 completed search, report filtering, pagination, filtered empty states, per-page selection, and results summaries. The next management-scale gap is that users can act on a single row or delete all saved views, but cannot select several specific saved views and apply a bulk action to only those selected records.

## Current state

- Single-row actions exist.
- Delete-all action exists.
- Selective bulk checkboxes are absent.
- Select-all checkbox is absent.
- Bulk delete form is absent.
- Bulk destroy route is absent.
- Bulk destroy controller method is absent.

## Phase 68B recommendation

Implement saved view management bulk selection.

Implementation targets:

- Add select-all and per-row checkboxes to the saved view management table.
- Add a bulk delete form that accepts selected saved view IDs only.
- Add a guarded controller action that deletes only the authenticated user's selected saved views.
- Add a route for the bulk delete action.
- Preserve Phase 67 search, filter, per-page, pagination, active-filter, and results-summary UX.
- Preserve Phase 66 ownership authorization and row action behavior.

## Risk

Risk level: medium.

Bulk deletion changes destructive-action scope and must not delete another user's saved views or accidentally behave like delete-all.

## Guardrails

- Do not change Phase 67 search query semantics.
- Do not change Phase 67 pagination or per-page behavior.
- Do not change Phase 67 filtered empty state behavior.
- Do not change Phase 66 authorization hardening.
- Do not weaken existing delete-all confirmation behavior.
- Bulk delete must be selection-scoped, not filter-scoped, unless explicitly implemented and tested later.
