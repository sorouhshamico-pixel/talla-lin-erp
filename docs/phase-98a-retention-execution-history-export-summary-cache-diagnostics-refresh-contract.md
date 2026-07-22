# Phase 98A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Contract

## Baseline

- Phase: Phase 97C
- Commit: `d4ff639482e1dc3aa3e16b107f03fa075f7aaba9`
- Full suite: 1973 passed
- Assertions: 18093
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 98A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, view, or JavaScript changes.

## Purpose

Allow an authorized administrator to refresh the existing Summary cache diagnostics snapshot without reloading the full administration page or recomputing the Export Summary.

## Planned endpoint

Method:

`GET`

URI:

`reports/saved-view-share-activity-retention/summary-cache-diagnostics`

Route name:

`reports.saved-view-share-activity-retention.summary-cache-diagnostics`

## Response

The endpoint returns the existing Diagnostics payload directly as JSON.

It does not wrap the payload.

It does not include the Export Summary or retention status.

## Authorization

The existing permission is reused:

`manage_saved_view_share_activity_retention`

No new permission is required.

Guest and unauthorized access remain forbidden.

## View behavior

The existing Diagnostics section gains a button:

`Refresh diagnostics`

The button:

- Uses `type="button"`
- Does not reload the full page
- Does not recompute the Export Summary
- Updates the Diagnostics section in place
- Displays loading, success, and failure states
- Is disabled while a request is active

## Client behavior

The client sends a GET request with:

`Accept: application/json`

No CSRF token is required.

Automatic polling is forbidden.

Concurrent requests are prevented.

Server-provided raw HTML is never inserted.

Displayed values are updated through safe text content operations.

## Privacy

The refresh response and UI never expose:

- Raw Generation Token
- Raw cache key
- Raw filters
- Actor user ID
- History payload
- Exception message
- Stack trace

## Performance

Each refresh allows:

- Maximum Cache reads: one
- Maximum database queries: zero
- Maximum Model hydration: zero
- Export Summary queries: zero

The response size remains constant.

## Planned implementation

Phase 98B may modify only:

- Administration Controller
- Web routes
- Existing administration View
- A focused Phase 98B test

It must not change Services, database, migrations, or Models.

## Compatibility

Existing page loading, Summary, JSON status payload, preview and execute endpoints, exports, Diagnostics payload, cache behavior, observability events, schema, and Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 98B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh.
