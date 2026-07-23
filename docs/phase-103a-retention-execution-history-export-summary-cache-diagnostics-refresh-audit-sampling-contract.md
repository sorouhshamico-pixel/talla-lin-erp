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

## Compatibility migration

Phase 101B allowed-request tests currently require exactly one `Log::info()` call. Sampling intentionally skips that call for unsampled allowed requests.

Phase 103B must therefore update both the Phase 101B and Phase 102B compatibility tests so every allowed-request case that expects `Log::info()` installs a deterministic Correlation ID that belongs to the 25 percent sampled bucket.

This update preserves the historical test intent:

- Sampled allowed requests still write the original Audit Event and Context
- Audit logging failures still preserve the response
- Limited-request tests remain unchanged
- No production exception is added for the test environment
- Sampling is not disabled during tests

## Planned implementation

Phase 103B may modify only:

- `app/Http/Middleware/AuditSavedViewRetentionSummaryCacheDiagnosticsRefresh.php`
- `tests/Feature/ReportSavedViewPhase101BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditTrailImplementationTest.php`
- `tests/Feature/ReportSavedViewPhase102BRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditCorrelationImplementationTest.php`
- One focused Phase 103B implementation test

Maximum modified files: four.

The Phase 101B and Phase 102B test updates are compatibility migrations, not relaxations of Runtime behavior.

Phase 103B must not modify Bootstrap, Routes, Controller, Services, Provider, Views, Layout, database, migrations, or Models.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 103B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Refresh Audit Sampling.
