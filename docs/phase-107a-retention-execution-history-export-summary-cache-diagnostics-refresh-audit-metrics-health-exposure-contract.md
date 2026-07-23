# Phase 107A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Exposure Contract

## Baseline

- Phase: Phase 106C
- Commit: `c32bd71c516f3bde26be5fd7009fbfdb022fef5f`
- Full suite: 2120 passed
- Assertions: 20225
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 107A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, view, layout, provider, bootstrap, Middleware, Event, Listener, Logging configuration, or Health class changes.

## Purpose

Expose the existing side-effect-free Audit Metrics Health Status through a protected JSON endpoint for authorized report users.

The endpoint must not modify the Health calculation, emit Events, write Logs, or access Database or Cache.

## Route

Method:

`GET`

URI:

`reports/saved-view-share-activity-retention/summary-cache-diagnostics/audit-metrics-health`

Name:

`reports.saved-view-share-activity-retention.summary-cache-diagnostics.audit-metrics-health`

Format:

JSON.

Middleware order:

- `auth`
- `can:manage_saved_view_share_activity_retention`

The Route is placed inside the existing authenticated retention administration group and inherits the same administrative authorization boundary.

No new Rate Limiter is added.

## Controller

Class:

`App\Http\Controllers\Reports\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController`

Method:

`__invoke`

Dependency:

`App\Support\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth`

The response is the Health Status Array without wrapping or transformation.

## Response

Success status:

200.

Content type:

`application/json`

Exact properties:

- `listener_discovered`
- `listener_count`
- `channel_configured`
- `channel_driver`
- `channel_level`
- `channel_retention_days`
- `channel_path_matches`
- `healthy`

Healthy and unhealthy results both use HTTP 200.

The `healthy` field communicates pipeline health.

## Authorization

Authentication is required.

Permission:

`manage_saved_view_share_activity_retention`

Authorization Middleware:

`can:manage_saved_view_share_activity_retention`

Guest JSON response:

401.

Authenticated user without permission:

403.

## Testability

The Health class is `final`.

Phase 107B must not Mock the final Health class directly.

Controller tests must instantiate the real Health class with a mocked Event Dispatcher to produce healthy or unhealthy results deterministically.

Route Source Guards must isolate the exact Route block by Route name before checking for forbidden Middleware, so adjacent Routes do not create false positives.

## Failure behavior

Unexpected Health calculation exceptions are already converted by the Health class into the locked unhealthy result.

The Controller adds no extra exception translation.

Unhealthy status is returned as JSON.

No exception details are exposed.

## Privacy

The endpoint does not expose:

- Correlation ID
- Raw user ID
- Raw IP address
- Session ID
- Request Headers
- Cookies
- Retry-After value
- Sampling bucket
- Diagnostics payload
- Cache key
- Filesystem contents
- Exception details

## Compatibility

The implementation must not change:

- Health class
- Listener
- Event
- Middleware
- Logging configuration
- Event dispatch count
- Metric Log write count
- Sampling rate or algorithm
- Limited Audit coverage
- Audit Log calls
- Existing Routes or Controller payloads
- Existing Response Headers
- Rate Limit behavior
- Diagnostics Service execution
- Correlation behavior

## Performance

The endpoint performs:

- Zero Event dispatches
- Zero Log writes
- Zero database queries
- Zero Cache reads
- Zero Cache writes
- Zero Model hydration
- Zero filesystem reads

## Planned implementation

Phase 107B may modify only:

- `app/Http/Controllers/Reports/SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController.php`
- `routes/web.php`
- One focused Phase 107B implementation test

Maximum modified files: three.

It must not modify Health class, Listener, Event, Middleware, Logging configuration, Bootstrap, Provider, Views, Layout, database, migrations, or Models.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 107B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Exposure.
