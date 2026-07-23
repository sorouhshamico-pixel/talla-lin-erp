# Phase 103A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Sampling Contract

## Baseline

- Phase: Phase 102C
- Commit: `39f88d0ba3717a9005e11933916ebbd65815f663`
- Full suite: 2052 passed
- Assertions: 19198
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 103A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, view, layout, provider, bootstrap, or Middleware changes.

## Purpose

Define deterministic privacy-safe Sampling for allowed Diagnostics Refresh Audit Events while preserving complete Audit coverage for limited requests.

Responses, Rate Limiting, Diagnostics Service execution, Correlation, and Observability remain unchanged.

## Sampling policy

Allowed Audit Events:

- Sampling enabled
- Sample rate: 25 percent

Limited Audit Events:

- Sampling disabled
- Recording rate: 100 percent

Decision source:

`correlation_id`

Decision algorithm:

SHA-256 modulo 100.

The same Correlation ID always produces the same Sampling decision.

No runtime random calls are permitted.

The Sampling percentage is a class constant and is not runtime mutable.

## Decision rules

Bucket range:

0 through 99.

An allowed request is sampled when its bucket is less than 25.

Limited requests bypass Sampling completely.

Missing or invalid Correlation IDs fail open and record the Audit Event.

## Audit behavior

Sampled allowed requests keep the existing Event name and Context unchanged.

Unsampled allowed requests skip only the Audit Log call.

Limited requests always execute the Audit Log call.

Limited Event names and Context remain unchanged.

Audit failures continue to preserve the original response.

Correlation generation remains unchanged.

## Privacy

The Sampling implementation never adds the raw Correlation ID, Sampling bucket, or Sampling decision to the Audit Context or response.

It never uses raw user ID, IP address, Session ID, Request Headers, or cookies.

## Performance

The implementation adds:

- Zero database queries
- Zero Cache reads
- Zero Cache writes
- Zero Model hydration
- Zero Summary queries
- At most one hash operation for allowed requests
- Zero hash operations for limited requests

## Compatibility

Route method, URI, name, permission, Controller payload, Response Headers, Rate Limit name and threshold, Retry-After behavior, Correlation behavior, limited Audit coverage, View, Layout, JavaScript behavior, Summary Cache behavior, Diagnostics Observability, History schema, and History Model remain unchanged.

## Planned implementation

Phase 103B may modify only:

- `app/Http/Middleware/AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php`
- One focused Phase 103B implementation test

It must not modify Bootstrap, Routes, Controller, Services, Provider, Views, Layout, database, migrations, or Models.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 103B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Sampling.
