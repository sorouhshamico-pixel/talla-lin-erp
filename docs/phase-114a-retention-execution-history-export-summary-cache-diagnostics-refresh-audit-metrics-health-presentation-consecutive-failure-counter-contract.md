# Phase 114A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Consecutive Failure Counter Contract

## Baseline

- Phase: Phase 113C
- Commit: `37d842d4d503739301ef7d1af3e9bb4220214fc3`
- Full suite: 2238 passed
- Assertions: 22108
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 114A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, View, Layout, Provider, Bootstrap, Middleware, Event, Listener, Logging configuration, or Health class changes.

## Purpose

Define client-side consecutive failure counting for the existing Audit Metrics Health presentation.

The implementation must not change endpoint payloads, authorization, request frequency, persistence, or backend behavior.

## Target Partial

`resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php`

## Counter element

- Element: `span`
- ID: `retention-audit-metrics-health-consecutive-failures`
- Prefix: `Consecutive failures:`
- Initial text: `0`
- `aria-live="off"`

## Counter state

- Variable: `consecutiveFailures`
- Initial value: zero
- Minimum: zero
- Maximum: 999
- Integer only
- Client memory only
- No Local Storage
- No Session Storage
- No database
- No Cache

## Classification

Success:

- Validated healthy response
- Validated unhealthy response

Failure:

- HTTP error response
- Network failure
- JSON parsing failure
- Payload validation failure

## Update rules

- Request start does not change the counter
- Success resets the counter to zero
- Failure increments the counter by one
- Counter is clamped to 999
- Ignored concurrent requests do not change it
- Every executed request updates the counter once
- Each failure type increments only once

## Display

- Zero displays as `0`
- Positive values display as decimal integers
- Invalid values fall back to `0`
- No suffix
- Relative wording is forbidden

## Accessibility

- Textual prefix remains present
- Automatic announcement remains disabled
- Counter remains outside the health status region
- Health status messages remain unchanged
- Visual state is not required to understand the counter

## Privacy

The presentation does not render failure reasons, exception messages, response bodies, response Headers, request URLs, user identifiers, Session identifiers, or Correlation IDs.

## Compatibility

Response status, request duration, refresh timestamp, health status messages, visual state, field rendering, payload validation, endpoint payload, Route, Controller, Health class, authorization, request frequency, database, Cache, Event, and Logging behavior remain unchanged.

No Polling or retry loop is added.

## Planned implementation

Phase 114B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 114B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 114B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Consecutive Failure Counter.
