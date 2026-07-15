# Phase 69A — Saved View Management Export Contract

## Baseline

- Previous phase: Phase 68E clean
- Commit: b24643c
- Previous tests: 1327 passed / 11697 assertions

## Purpose

Prepare the contract for exporting the saved view management list.

Phase 67 stabilized search, filtering, pagination, empty states, per-page selection, and results summaries. Phase 68 stabilized bulk selection and bulk deletion. The next low-risk management enhancement is read-only CSV export for the filtered saved view list.

This phase is contract-only and must not change implementation files.

## Current state

- Management search/filter/pagination exists.
- Bulk selection exists.
- Bulk delete context preservation exists.
- Export route is absent.
- Export controller action is absent.
- Export link/button is absent.
- Export implementation tests are absent.

## Phase 69B recommendation

Implement Saved View Management CSV Export.

Implementation targets:

- Add a user-scoped export route for the saved view management list.
- Add a controller action that exports only the authenticated user's saved views.
- Honor current management filters: `search` and `report_key`.
- Do not depend on `page` or `per_page`; export the full filtered result set.
- Include stable CSV columns:
  - name
  - report label
  - report key
  - default flag
  - filter count
  - filters summary
  - updated_at
- Add an export link/button to the management page that preserves active filters.
- Do not change Phase 67 pagination, Phase 68 bulk selection, or Phase 66 row action behavior.

## Risk

Risk level: low-medium.

Export is read-only, but must remain user-scoped and must not accidentally expose saved views belonging to another user.

## Guardrails

- Do not export saved views owned by another user.
- Do not make export page-scoped unless explicitly decided later.
- Do not include raw sensitive values beyond the existing saved view filters summary unless reviewed.
- Do not change saved view management pagination semantics.
- Do not change bulk delete selection or context behavior.
- Do not change row action authorization.
