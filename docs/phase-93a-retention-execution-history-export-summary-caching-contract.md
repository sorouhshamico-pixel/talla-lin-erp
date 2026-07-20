# Phase 93A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Caching Contract

## Baseline

- Phase: Phase 92C
- Commit: `d07e027fdb831701bb38688ea8d16d6faf3d05fb`
- Full suite: 1892 passed
- Assertions: 17263
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 93A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Reduce repeated aggregate Summary queries for identical administration filters while preserving bounded staleness and export correctness.

## Cache policy

The existing Laravel cache store is reused.

The Summary cache TTL is 30 seconds.

Cache-forever, cache tags, distributed locks, and stale-while-revalidate are not required.

## Cache key

The key uses the prefix:

`reports:saved-view-retention:execution-history-summary:v1`

The key is based on normalized values for:

- `type`
- `status`
- `actor_user_id`
- `started_from`
- `started_to`

Filter ordering must not affect the key.

Null and empty filters are equivalent.

A SHA-256 digest is required so raw filter values are not appended directly to the key.

## Cache value

The cached value is the existing plain Summary array.

Models, Builders, and Closures must not be cached.

The Summary payload and ISO 8601 timestamps remain unchanged.

## Request behavior

The HTML administration Summary uses the cache.

The JSON status response and CSV and JSON exports do not use the Summary cache.

A cache hit executes zero Summary database queries.

A cache miss executes the existing one aggregate query.

## Invalidation

The 30-second TTL is the primary invalidation mechanism.

No write-event invalidation, manual flush Route, database trigger, or retention-completion flush is required.

Maximum accepted staleness is 30 seconds.

## Failure behavior

Cache failures fall back to the live Summary and do not break the administration page.

Sensitive filters are not logged as part of cache failure handling.

## Planned implementation

Phase 93B may modify only:

- `ReportSavedViewShareActivityRetentionExecutionHistoryExportService`
- A focused Phase 93B test

It must not change the Controller, View, Routes, database, or migrations.

## Compatibility

The Summary payload, filter semantics, empty state, administration HTML and JSON, exports, limits, presets, date shortcuts, history schema, and history Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 93B — Implement Saved View Sharing Activity Retention Execution History Export Summary Caching.
