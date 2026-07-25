# Phase 112A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Request Duration Contract

## Baseline

- Phase: Phase 111C
- Commit: `8078605483d11de44c02aafe300588fa13e79e5e`
- Full suite: 2202 passed
- Assertions: 21564
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 112A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, View, Layout, Provider, Bootstrap, Middleware, Event, Listener, Logging configuration, or Health class changes.

## Purpose

Define client-side request-duration semantics for the existing Audit Metrics Health presentation.

The implementation must not change endpoint payloads, authorization, request frequency, persistence, server timing, or Health calculation.

## Target Partial

`resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php`

## Duration element

- Element: `span`
- ID: `retention-audit-metrics-health-request-duration`
- Prefix: `Last request duration:`
- Initial text: `Not measured yet`
- `aria-live="off"`

## Measurement

- Clock: `performance.now()`
- Start point: immediately before Fetch
- End point: request `finally` block
- Unit: milliseconds
- Minimum: zero
- Negative values are forbidden
- No Server-Timing Header
- No Date clock
- No endpoint payload duration

## Formatting

Less than one second:

- Display in milliseconds
- No fractional digits
- Suffix: `ms`

One second or more:

- Divide by 1000
- Maximum two fractional digits
- Suffix: `s`

Primary formatter:

`Intl.NumberFormat`

Fallback:

`Number.prototype.toFixed`

Invalid value:

`Not measured yet`

## Update rules

The previous duration is not cleared when a request starts.

The duration updates exactly once after each completed request, including:

- Validated healthy response
- Validated unhealthy response
- Request failure
- JSON parsing failure
- Payload validation failure

Ignored concurrent requests do not update it.

## Accessibility

- Textual prefix remains present
- Automatic announcement remains disabled
- Duration remains outside the status region
- Existing status messages remain unchanged
- Visual state is not required to understand duration

## Privacy

No Server-Timing value, start timestamp, end timestamp, response Headers, endpoint payload duration, user identifier, Session identifier, or Correlation ID is rendered.

## Compatibility

Refresh timestamp, status messages, visual state, field rendering, payload validation, endpoint payload, Route, Controller, Health class, authorization, request frequency, database, Cache, Event, and Logging behavior remain unchanged.

No Polling or retry loop is added.

## Planned implementation

Phase 112B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 112B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 112B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Request Duration.
