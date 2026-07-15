# Phase 70A — Saved View Management Import Contract

## Baseline

- Previous phase: Phase 69C clean
- Commit: 9917445
- Previous tests: 1349 passed / 11949 assertions

## Purpose

Prepare the contract for saved view management import/restore.

Phase 69 stabilized read-only CSV export. Import is higher risk because it is eventually write-capable. This phase is contract-only and must not change implementation files.

## Current state

- CSV export route exists.
- CSV export controller action exists.
- CSV export service query exists.
- CSV export link exists.
- Management search, filtering, pagination, and summaries exist.
- Bulk selection and bulk delete context preservation exist.
- Import route is absent.
- Import controller action is absent.
- Import form/link is absent.
- Import validation is absent.
- Import preview is absent.
- Import write tests are absent.

## Phase 70B recommendation

Implement Saved View Management Import Preview.

Implementation targets:

- Add an import entry point to the saved view management page.
- Accept CSV input using the same stable export columns where possible.
- Validate headers before processing rows.
- Preview parsed rows without writing any saved views.
- Show row-level validation results for:
  - `report_key`
  - `name`
  - `is_default`
  - `filters_summary`
- Require authenticated user scope for all preview/import actions.
- Do not write database rows in the preview phase.
- Do not change Phase 69 CSV export behavior.
- Do not change Phase 68 bulk selection behavior.
- Do not change Phase 67 pagination behavior.

## Risk

Risk level: medium-high.

Import is write-capable once completed and can create, overwrite, or corrupt saved view data if implemented without a preview and validation gate.

## Guardrails

- Start with preview-only import before any write-capable import.
- Do not create or update saved views during Phase 70B preview.
- Do not accept unknown `report_key` values.
- Do not import saved views for another user.
- Do not silently overwrite existing saved views.
- Do not trust CSV `filters_summary` as a complete source for machine-readable filters without a parsing contract.
- Keep Phase 69 export read-only and stable.
- Keep Phase 68 bulk selection stable.
- Keep Phase 67 management pagination stable.
