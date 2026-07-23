# Phase 108A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Contract

## Baseline

- Phase: Phase 107C
- Commit: `ecbf3111d8ff57a96d7e96bb99c02a4197ad727d`
- Full suite: 2136 passed
- Assertions: 20490
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 108A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, view, layout, provider, bootstrap, Middleware, Event, Listener, Logging configuration, or Health class changes.

## Purpose

Present the existing Audit Metrics Health endpoint inside the retention administration interface as a read-only status panel.

The presentation must not change the endpoint, Health calculation, Route authorization, Audit behavior, Rate Limiting, or persistence.

## Placement

Parent View:

`resources/views/reports/saved-views/share-activity-retention.blade.php`

Section:

The existing `retention-summary-cache-diagnostics-heading` section.

Position:

After the complete Summary Cache Diagnostics section and before the existing Privacy Notice paragraph.

Parent insertion anchor:

`Privacy notice: context and updated_at are excluded from exports.`

Required Partial:

`resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php`

## Presentation

Title:

`Audit Metrics Health`

Initial state:

Loading.

Status labels:

- Healthy
- Unhealthy
- Unavailable

Displayed fields:

- `listener_discovered`
- `listener_count`
- `channel_configured`
- `channel_driver`
- `channel_level`
- `channel_retention_days`
- `channel_path_matches`
- `healthy`

Raw JSON and exception details are not displayed.

## Client behavior

The panel performs one GET request to:

`reports.saved-view-share-activity-retention.summary-cache-diagnostics.audit-metrics-health`

Behavior:

- Automatic request on page load
- Manual Refresh button
- Prevent concurrent requests
- No Polling
- No retry loop
- No page reload
- No additional timeout mechanism
- Fetch credentials: `same-origin`
- Accept header: `application/json`
- Script location: inside the new Partial
- DOM initialization: immediate when ready or through `DOMContentLoaded`

## States

Loading:

`Loading health status...`

Healthy:

`Audit metrics pipeline is healthy.`

Unhealthy:

`Audit metrics pipeline requires attention.`

Unavailable:

`Audit metrics health status is unavailable.`

Unavailable is used for HTTP or JSON parsing failures.

## Accessibility

- Status region role: `status`
- `aria-live="polite"`
- Refresh element uses `type="button"`
- Field labels use table headers
- Status must not depend on color alone

## Privacy

The panel does not render Correlation IDs, user identifiers, IP addresses, Session data, Headers, Cookies, Retry-After values, Sampling buckets, Diagnostics payloads, Cache keys, filesystem contents, or exception details.

## Compatibility

The implementation must not change the Health endpoint, Health class, Controller, Route, authorization, Listener, Event, Middleware, Logging configuration, Rate Limiter, Audit Middleware, existing Summary Cache Diagnostics controls, existing form submissions, or existing response payloads.

## Performance

- One Health request on initial page load
- One Health request per manual refresh
- Zero Polling requests
- Zero database queries
- Zero Cache operations
- Zero Event dispatches
- Zero Log writes

## Planned implementation

Phase 108B may modify only:

- `resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php`
- `resources/views/reports/saved-views/share-activity-retention.blade.php`
- One focused Phase 108B implementation test

Maximum modified files: three.

It must not modify the endpoint Controller, Health class, Route, Listener, Event, Middleware, Logging configuration, Layout, Provider, database, migrations, or Models.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 108B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation.
