# Phase 66E — Saved View Management Table Actions UX Cleanup

## Baseline

- Previous phase: Phase 66D clean
- Commit: ba529f5
- Previous tests: 1242 passed / 10966 assertions

## Purpose

Reduce saved view management table action density by grouping row actions into clear action clusters.

## Scope

This phase changes only the saved views management index view, plus tests and documentation.

## UX contract

The actions column is now labeled `الإجراءات` and row actions are grouped into:

- Primary actions: فتح التقرير، تطبيق
- Secondary actions: تعديل، نسخ، تعيين افتراضي
- Danger actions: حذف

Existing action routes, methods, and `data-testid` markers are preserved.

## Resolved audit finding

- `index_action_density`

## Guardrails

- Do not alter saved view authorization behavior.
- Do not alter saved view registry lookups.
- Do not change saved view edit filter read-only behavior.
- Preserve existing action button/link test IDs.
