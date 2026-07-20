# Phase 88A — Prepare Saved View Sharing Activity Retention Execution History Export Administration Contract

## Baseline

- Phase: Phase 87C
- Commit: `811fdf60db903ba783bf1869b9770ca606748b65`
- Full suite: 1818 passed
- Assertions: 16416
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 88A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

The next implementation phase will expose guarded CSV and JSON export controls from the existing retention administration page.

The existing export Controller, Service, and Routes remain authoritative and will not be duplicated.

## Existing interface reuse

- Controller: `App\Http\Controllers\ReportSavedViewShareActivityRetentionAdminController`
- Status route: `reports.saved-view-share-activity-retention.index`
- View: `resources/views/reports/saved-views/share-activity-retention.blade.php`
- CSV route: `reports.saved-view-share-activity-retention.history.export.csv`
- JSON route: `reports.saved-view-share-activity-retention.history.export.json`

No new Controller, Service, or Route is required.

## Authorization

Access requires authentication and the existing permission:

`manage_saved_view_share_activity_retention`

Ownership or recipient scope alone is not sufficient.

## Export controls

The administration page must provide:

- A filter form for `type`, `status`, `actor_user_id`, `started_from`, and `started_to`
- A CSV export action
- A JSON export action
- A clear-filters action
- Query-string forwarding for non-empty filters
- Visible row-limit information
- A privacy notice explaining that `context` and `updated_at` are excluded

Server-side validation and row limits remain authoritative.

## Privacy and behavior

The page must not render context payloads, full activity metadata, filter payloads, credentials, or secrets.

The browser must not materialize export datasets client-side.

Export requests remain GET requests and must not mutate execution history or create sharing-activity rows.

## Compatibility

Phase 88B must not change the export engine, export Routes, history schema, history model, history read route, retention administration Routes, retention policy, retention command signature, or scheduler contract.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 88B — Implement Saved View Sharing Activity Retention Execution History Export Administration.
