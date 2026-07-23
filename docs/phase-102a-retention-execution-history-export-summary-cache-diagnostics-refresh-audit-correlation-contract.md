# Phase 102A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Correlation Contract

## Baseline

- Phase: Phase 101C
- Commit: `6270658157ee9f3985753009d3d771a939c95bd3`
- Full suite: 2036 passed
- Assertions: 18953
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 102A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, view, layout, provider, bootstrap, or Middleware changes.

## Purpose

Define a privacy-safe per-request Correlation ID for Diagnostics Refresh Audit Events without changing the response payload, response headers, Rate Limiting, Diagnostics Service execution, or existing Observability events.

## Planned identifier

Context key:

`correlation_id`

Generation:

Random UUID v4.

Rules:

- Generated once per request
- Stable throughout the same request
- Unique across separate requests
- Never accepted from a client-supplied value
- Never read from request headers
- Never returned in response headers
- Never added to the response body
- Never persisted to database, Cache, or Session

## Format

- UUID v4
- Canonical length: 36
- Lowercase
- Hyphenated
- Contains no user, IP, Session, or timestamp data

## Audit context

The same `correlation_id` field is added to both:

- Allowed Audit Event
- Limited Audit Event

All existing Audit fields remain unchanged.

## Forbidden sources

The Correlation ID must never derive from:

- Raw user ID
- Raw IP address
- Raw limiter key
- Session ID
- Request ID headers
- Traceparent header
- X-Request-ID header
- Cookies
- Authorization header
- Diagnostics payload
- Generation Token
- Cache key

## Behavior

Allowed and limited requests each receive exactly one Correlation ID.

Limited requests still never reach Controller or Diagnostics Service.

Audit failures still never change the response.

Existing Audit Event names, Observability Event names, and Rate Limit behavior remain unchanged.

Correlation generation failure is not silently swallowed because it would prevent a valid Audit Event from being constructed.

## Performance

The Correlation feature adds:

- Zero database queries
- Zero Cache reads
- Zero Cache writes
- Zero Model hydration
- Zero Summary queries

## Compatibility

Route method, URI, name, permission, Controller payload, response headers, Rate Limit name and threshold, Retry-After behavior, View, Layout, JavaScript behavior, Summary Cache behavior, Diagnostics Observability, History schema, and History Model remain unchanged.

## Planned implementation

Phase 102B may modify only:

- `app/Http/Middleware/AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php`
- One focused Phase 102B implementation test

It must not modify Bootstrap, Route, Controller, Services, Provider, Views, Layout, database, migrations, or Models.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 102B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Correlation.
