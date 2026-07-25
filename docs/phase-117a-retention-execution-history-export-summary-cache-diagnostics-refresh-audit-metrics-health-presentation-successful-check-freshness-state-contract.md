# Phase 117A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Successful Check Freshness State Contract

## Baseline

- Phase: Phase 116C
- Commit: `1fa4460c3efb43ea480f808dccfe12cfb5561f0b`
- Full suite: 2290 passed
- Assertions: 23018
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 117A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, View, Layout, Provider, Bootstrap, Middleware, Event, Listener, Logging configuration, or Health class changes.

## Purpose

Define a client-side freshness state for the most recent validated healthy Audit Metrics Health check.

The state reuses the existing Successful Check Age data and does not change endpoint payloads, authorization, request frequency, persistence, or backend behavior.

## Target Partial

`resources/views/reports/saved-views/partials/share-activity-retention-audit-metrics-health.blade.php`

## Element

- Element: `span`
- ID: `retention-audit-metrics-health-successful-check-freshness`
- Prefix: `Successful check freshness:`
- Initial text: `Unavailable`
- `aria-live="off"`

## Freshness states

Fresh:

- Text: `Fresh`
- Age: 0 through 14 minutes inclusive

Stale:

- Text: `Stale`
- Age: 15 minutes or more

Unavailable:

- Text: `Unavailable`
- Used when there is no valid successful check Date

## Source

- State variable: `lastSuccessfulCheckAt`
- Current time argument: `currentTime`
- Age minutes use floor rounding
- Negative age clamps to zero
- No server timestamp
- No payload timestamp
- No response Header timestamp
- No parsing of rendered age text

## Update rules

- Request start does not clear the previous value
- Validated Healthy updates freshness
- Validated Unhealthy does not update freshness
- HTTP, Network, Parsing, and Validation failures do not update freshness
- Ignored concurrent requests do not update freshness
- Freshness uses the same `completedAt` Date as Last Successful Check
- One update per Validated Healthy request
- No background Timer
- No Polling

## Visual state

The freshness element uses:

`data-freshness-state`

Allowed values:

- `fresh`
- `stale`
- `unavailable`

Initial value:

`unavailable`

No CSS class change is required.

The panel health state and indicator state remain unchanged.

## Accessibility

- Textual prefix remains present
- Automatic announcement remains disabled
- Element remains outside the Health status region
- Meaning is expressed in text, not color only
- Last Successful Check remains the primary timestamp
- Successful Check Age remains the primary age
- Health status messages remain unchanged

## Privacy

No server timestamp, payload timestamp, response Header, request URL, exception message, user identifier, Session identifier, or Correlation ID is rendered.

## Compatibility

Successful Check Age, Last Successful Check, Consecutive Failure Counter, Response Status, Request Duration, Refresh Timestamp, Health Status Messages, Visual State, Field Rendering, Payload Validation, endpoint payload, Route, Controller, Health class, authorization, request frequency, database, Cache, Event, and Logging behavior remain unchanged.

No Polling or Retry loop is added.

## Planned implementation

Phase 117B may modify only:

- Existing Audit Metrics Health Partial
- One focused Phase 117B implementation test

Maximum modified files: two.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 117B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Metrics Health Presentation Successful Check Freshness State.
