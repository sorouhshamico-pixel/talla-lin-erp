# Phase 101A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Trail Contract

## Baseline

- Phase: Phase 100C
- Commit: `f40b515b5e86e1fc406918644aa48e84fc81a9df`
- Full suite: 2020 passed
- Assertions: 18707
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 101A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, view, layout, or provider changes.

## Purpose

Define a privacy-safe append-only Audit Trail for Diagnostics refresh attempts without changing the Diagnostics payload, Rate Limiting behavior, Observability events, or the existing Retention Execution History schema.

## Planned audit events

Allowed request:

`saved_view_retention.summary_cache_diagnostics.refresh_audit.allowed`

Limited request:

`saved_view_retention.summary_cache_diagnostics.refresh_audit.limited`

## Planned storage

The Audit Trail uses the application log.

It does not add or reuse a database table.

It adds no Migration or Model.

It remains append-only.

## Allowed context

Allowed request context:

- event
- outcome
- route_name
- request_method
- authenticated
- permission_checked
- rate_limit_name

Limited request context:

- event
- outcome
- route_name
- request_method
- authenticated
- permission_checked
- rate_limit_name
- retry_after_seconds

## Forbidden context

The Audit Trail never includes:

- Raw user ID
- Raw IP address
- Raw limiter key
- Raw Generation Token
- Raw cache key
- Raw filters
- Actor user ID
- History payload
- Diagnostics payload
- Exception message
- Stack trace
- Request headers
- Session ID
- Cookies

## Behavior

Allowed requests write Audit data only after the Rate Limiter allows the request.

Limited requests write Audit data without Controller execution.

Allowed requests call the Diagnostics Service once.

Limited requests never call the Diagnostics Service.

Audit failures are swallowed and never change the response.

Existing Observability events, HTTP 429 behavior, and Diagnostics payload remain unchanged.

## Performance

The Audit Trail adds:

- Zero database queries
- Zero Model hydration
- Zero Summary queries
- Zero Diagnostics Cache reads
- Zero Diagnostics calls for limited requests

## Compatibility

The Route method, URI, name, permission, Controller payload, Rate Limit, Retry-After behavior, View, Layout, JavaScript behavior, Summary Cache behavior, Diagnostics Observability, History schema, and History Model remain unchanged.

## Planned implementation

Preferred location:

Custom Route Middleware.

Planned Middleware:

`AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh`

Phase 101B may modify only the Middleware registration path, the target Route, the new Middleware, and one focused implementation test.

It must not modify Controller, Services, Provider, Views, Layout, database, migrations, or Models.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 101B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Trail.
