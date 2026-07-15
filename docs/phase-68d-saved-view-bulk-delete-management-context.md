# Phase 68D — Saved View Bulk Delete Management Context Preservation

## Baseline

- Previous phase: Phase 68C clean
- Commit: c5eb843
- Previous tests: 1309 passed / 11545 assertions

## Purpose

Preserve the current saved view management context after bulk deletion.

Before this phase, bulk delete returned to the plain saved view management index. That lost the user's active search, selected report filter, per-page value, and current page.

## Implemented behavior

- The bulk delete form carries the current search value.
- The bulk delete form carries the current report filter.
- The bulk delete form carries the current per-page value.
- The bulk delete form carries the current page.
- The bulk destroy action validates return context fields.
- The bulk destroy redirect appends valid context back to the management index.
- Invalid return report keys are dropped.
- Bulk delete ownership scope remains unchanged.
- Phase 68C selected-count and disabled-button UX remain preserved.
- Phase 67 search, report filter, per-page, pagination, active-filter, empty-state, and result-summary UX remain preserved.

## Guardrails

- Do not change route definitions.
- Do not change bulk delete ownership scope.
- Do not change saved view service pagination behavior.
- Do not change row delete, delete-all, duplicate, default, edit, or apply actions.
- Do not remove Phase 68C disabled-button and selected-count UX.
- Only valid management return context may be appended to the redirect URL.
