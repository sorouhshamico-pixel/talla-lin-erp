# Phase 90A — Prepare Saved View Sharing Activity Retention Execution History Export Date Range Shortcuts Contract

## Baseline

- Phase: Phase 89C
- Commit: `2e3be6d6b1a686836583b2b455a98eb2e65cfc6c`
- Full suite: 1847 passed
- Assertions: 16733
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 90A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Provide fixed date-range shortcuts for the existing retention execution-history export filters.

## Locked shortcuts

- Today
- Last 7 days
- Last 30 days
- This month
- Previous month
- Clear date range

## Time semantics

All shortcut boundaries are generated server-side in UTC.

The output format is `Y-m-d\TH:i`, matching the existing `datetime-local` filter inputs.

`started_from` and `started_to` remain inclusive according to the existing export filter contract.

## Behavior

Shortcuts link to the existing administration route.

They set only `started_from`, `started_to`, and the visible shortcut key.

Existing non-date filters remain preserved.

Shortcuts do not trigger export automatically.

Manual date editing remains available.

Unknown shortcut values receive no special server-side behavior.

## Implementation model

The shortcuts are fixed application-defined links.

They are not stored in the database, session, or cache.

No new Model, Service, Controller, Route, table, or migration is required.

## Presentation

The administration page must show a date-range shortcut section with visible labels and an active shortcut indication.

No JavaScript is required.

## Privacy

Shortcuts do not embed export rows, context payloads, full activity metadata, credentials, or secrets.

## Compatibility

Phase 90B must not change existing presets, manual filters, Controllers, Services, Routes, export row limits, history schema, history Model, or retention policy.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 90B — Implement Saved View Sharing Activity Retention Execution History Export Date Range Shortcuts.
