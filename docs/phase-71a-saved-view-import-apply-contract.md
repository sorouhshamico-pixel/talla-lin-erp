# Phase 71A — Saved View Import Apply Contract

## Baseline

- Previous phase: Phase 70C clean
- Commit: 9525972
- Previous tests: 1373 passed / 12225 assertions

## Purpose

Prepare the contract for the write-capable saved view import apply workflow.

Phase 70 stabilized preview-only import. Applying import is higher risk because it can create or alter saved views. This phase is contract-only and must not change implementation files.

## Current state

- Import preview route exists.
- Import preview controller action exists.
- Import preview form exists.
- Import preview panel exists.
- CSV header validation exists.
- Row-level validation exists.
- Import preview remains read-only.
- Write-capable import route is absent.
- Write-capable import controller action is absent.
- Write-capable import form is absent.
- Import apply tests are absent.
- CSV export remains present.
- Bulk selection remains present.
- Pagination remains present.

## Phase 71B recommendation

Implement Saved View Import Apply Workflow.

Implementation targets:

- Add a dedicated write-capable import apply route separate from preview.
- Require a previously validated CSV payload or explicit resubmission through the same validation rules.
- Validate required headers before any writes.
- Validate every row before any writes.
- Reject unknown `report_key` values.
- Create saved views only for the authenticated user.
- Use a database transaction for the apply operation.
- Do not silently overwrite existing saved views.
- Handle duplicate `name` and `report_key` pairs explicitly.
- Normalize `is_default` so only one default saved view exists per report per user.
- Return an apply summary with created, skipped, and failed counts.
- Keep Phase 70 preview available and read-only.
- Keep Phase 69 export stable.
- Keep Phase 68 bulk selection stable.
- Keep Phase 67 pagination stable.

## Risk

Risk level: high.

Import apply is write-capable and can create or alter user saved views. It needs all-or-nothing validation, explicit duplicate policy, and transaction boundaries before implementation.

## Guardrails

- Do not add write-capable import in this contract phase.
- Do not create or update `report_saved_views` rows in this phase.
- Write-capable import must be separate from preview.
- Write-capable import must use a database transaction.
- Do not import rows with unknown `report_key` values.
- Do not silently overwrite existing saved views.
- Do not import saved views for another user.
- Do not use `filters_summary` as machine-readable filters without a separate parsing contract.
- Keep import preview read-only.
- Keep CSV export read-only and stable.
- Keep bulk selection and pagination stable.
