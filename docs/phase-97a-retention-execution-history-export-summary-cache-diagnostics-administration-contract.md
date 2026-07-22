# Phase 97A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Administration Contract

## Baseline

- Phase: Phase 96C
- Commit: `b664ff68e9425b23621e238d96f6a4c3bce61eb6`
- Full suite: 1958 passed
- Assertions: 17919
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 97A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Expose the existing Summary cache diagnostics snapshot in the existing retention administration HTML page.

The current JSON status response must remain unchanged.

## Existing integration point

The existing Controller method is:

`ReportSavedViewShareActivityRetentionAdminController::index()`

The existing View is:

`reports.saved-views.share-activity-retention`

The planned View variable is:

`exportSummaryCacheDiagnostics`

## Request behavior

HTML requests compute both the existing Summary and the Diagnostics snapshot.

JSON status requests return the existing status payload only.

JSON requests skip both Summary and Diagnostics.

CSV and JSON exports remain unchanged.

## Display

The diagnostics section is read-only and appears near the Export Summary.

It contains no actions.

Displayed status fields:

- Cache store
- Cache-read availability
- Generation presence
- Generation source
- Summary TTL
- Generation TTL
- Observability status

Technical prefixes may be displayed:

- Cache-key prefix
- Generation-key prefix

Raw Generation Token and raw cache key are never displayed.

## Presentation

Availability, generation presence, and observability use explicit readable labels.

A fallback generation source displays a warning.

A default generation source is informational.

A cache generation source is healthy.

## Security

The existing permission is reused:

`manage_saved_view_share_activity_retention`

No new permission or authorization policy is required.

Diagnostics are not added to the JSON status response or exports.

## Performance

For HTML requests:

- Maximum additional Cache reads: one
- Maximum additional database queries: zero
- Maximum additional Model hydration: zero

For JSON status requests:

- Additional Cache reads: zero
- Additional database queries: zero

## Planned implementation

Phase 97B may modify only:

- Administration Controller
- Existing retention administration View
- A focused Phase 97B test

It must not change Services, Routes, database, or migrations.

## Compatibility

Existing filters, Summary, JSON status payload, preview and execute endpoints, exports, cache behavior, diagnostics payload, schema, and Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 97B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Administration.
