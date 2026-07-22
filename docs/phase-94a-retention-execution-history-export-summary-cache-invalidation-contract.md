# Phase 94A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Invalidation Contract

## Baseline

- Phase: Phase 93C
- Commit: `f497728ebaf351c1a60014f09bdbe0d8ac2680c0`
- Full suite: 1908 passed
- Assertions: 17422
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 94A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Make newly recorded retention execution history visible to every cached Summary filter combination immediately after a successful history write.

## Strategy

Use a Generation Token with the key:

`reports:saved-view-retention:execution-history-summary:generation:v1`

The token is a random UUID retained for 86400 seconds.

The Summary cache key includes the current Generation Token.

Changing the token makes all previous filter-specific Summary keys unreachable without cache tags, key enumeration, or a global cache flush.

## Write behavior

All success, failure, and conflict history records pass through the existing private `write()` method.

After a successful database create, the history Service replaces the Generation Token.

A failed history create does not invalidate the Summary cache.

Invalidation occurs after the database write.

Cache invalidation failure must not fail an otherwise successful history write.

## Read behavior

The Summary Service reads the Generation Token before constructing its filter-specific key.

A missing token or token-cache failure uses a stable default.

The JSON status response and CSV and JSON exports do not read the Generation Token.

The existing live-Summary fallback remains unchanged.

## Freshness

The next HTML Summary request after a successful history write uses the new generation and observes the new record.

Old Summary entries remain harmless and expire through the existing 30-second TTL.

No manual global cache flush is required.

## Privacy

The Generation Token contains no filter values, user ID, or history payload.

Sensitive filters are not logged.

## Planned implementation

Phase 94B may modify only:

- `ReportSavedViewShareActivityRetentionExecutionHistoryExportService`
- `ReportSavedViewShareActivityRetentionExecutionHistoryService`
- A focused Phase 94B test

It must not change the Controller, View, Routes, database, or migrations.

## Compatibility

Summary payload, filters, TTL, administration HTML and JSON, history write error logging, exports, presets, date shortcuts, history schema, and history Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 94B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Invalidation.
