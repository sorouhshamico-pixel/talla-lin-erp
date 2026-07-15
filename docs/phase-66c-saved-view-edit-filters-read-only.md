# Phase 66C — Saved View Edit Filters Read Only

## Baseline

- Previous phase: Phase 66B clean
- Commit: 09f9e39
- Previous tests: 1228 passed / 10888 assertions

## Decision

Saved view filters in the management edit screen are read-only.

## Reason

The edit page already communicates that saved filters are read-only. Before this phase, the same page still rendered editable filter inputs and the controller accepted a generic `filters` payload. That created a mismatch between the UX copy and actual behavior.

## Scope

Implementation scope is intentionally narrow:

- `app/Http/Controllers/ReportSavedViewController.php`
- `resources/views/reports/saved-views/edit.blade.php`
- `tests/Feature/ReportSavedViewEditTest.php`
- `tests/Feature/ReportSavedViewPhase66CSavedViewEditFiltersReadOnlyTest.php`
- This documentation and JSON contract

## Behavior after this phase

- The edit form updates the saved view name.
- The edit form updates the default state.
- Saved filter payloads are displayed for review.
- Saved filter payloads are not rendered as editable inputs.
- Submitted `filters[...]` request data is ignored by the update action.
- Existing saved filters are preserved during name/default updates.

## Resolved audit finding

- `edit_filter_mutation_risk`
