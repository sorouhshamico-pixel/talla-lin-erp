# Phase 90C — Finalize Saved View Sharing Activity Retention Execution History Export Date Range Shortcuts

## Baseline

- Phase: Phase 90B
- Commit: `3ee97bb9f291885bd80c9bd0d99bd09d5ac45484`
- Full suite: 1857 passed
- Assertions: 16828
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 90C is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Locked date range shortcuts

The existing retention administration page provides:

- Today
- Last 7 days
- Last 30 days
- This month
- Previous month
- Clear date range

## Time semantics

All boundaries are generated server-side in UTC using `Carbon::now('UTC')`.

The output format is `Y-m-d\TH:i`, matching the existing `datetime-local` inputs.

`started_from` and `started_to` remain inclusive.

## Behavior

Shortcuts link to the existing administration route with GET query parameters.

They preserve:

- `preset`
- `type`
- `status`
- `actor_user_id`

They do not trigger CSV or JSON export automatically.

Manual date editing remains available.

The active shortcut is identified in the rendered page.

No JavaScript is required.

## Persistence

Date range shortcuts are not stored in:

- Database
- Session
- Cache
- Local storage

## Locked implementation scope

Phase 90B changed only:

- `resources/views/reports/saved-views/share-activity-retention.blade.php`
- `tests/Feature/ReportSavedViewPhase90BRetentionExecutionHistoryExportDateRangeShortcutsImplementationTest.php`

It did not change Controllers, Services, Routes, database schema, or migrations.

## Privacy

The shortcut links do not embed export rows, context payloads, full activity metadata, credentials, or secrets.

## Compatibility

Existing presets, manual filters, administration and export interfaces, row limits, history schema, history Model, and retention policy remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 91A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Contract.
