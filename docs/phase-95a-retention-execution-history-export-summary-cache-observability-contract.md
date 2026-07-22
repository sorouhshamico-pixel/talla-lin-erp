# Phase 95A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Observability Contract

## Baseline

- Phase: Phase 94C
- Commit: `44a3c29bfada9990afe829c9c9c042d3c70f80f6`
- Full suite: 1924 passed
- Assertions: 17587
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 95A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Expose bounded, privacy-safe diagnostics for Summary cache behavior without changing the Summary payload or creating database records.

## Events

The planned structured events are:

- `saved_view_retention.summary_cache.hit`
- `saved_view_retention.summary_cache.miss`
- `saved_view_retention.summary_cache.fallback`
- `saved_view_retention.summary_cache.generation_read_fallback`
- `saved_view_retention.summary_cache.generation_rotated`
- `saved_view_retention.summary_cache.generation_rotation_failed`

## Logging levels

Cache hit, miss, and successful generation rotation use Debug.

Cache fallback, generation-read fallback, and generation-rotation failure use Warning.

The default application log channel is reused.

## Allowed context

Only bounded metadata may be logged:

- Event name
- Cache-key prefix
- Whether a generation was present
- Number of active filters
- TTL
- Fallback exception class
- Operation type
- Operation status

## Forbidden context

The following must not be logged:

- Raw cache key
- Generation token
- Raw filters
- Actor user ID
- History payload
- Failure message
- Stack trace

## Behavior

A hit is logged after a successful cache read.

A miss is logged when the live Summary callback runs.

Fallback is logged when `Cache::remember` fails.

Generation-read fallback is logged when reading the generation token fails.

Successful and failed generation rotations are logged after `Cache::put` succeeds or fails.

Logging failures must not change business behavior.

## Metrics strategy

Phase 95 uses log-based observability only.

No database table, database counter, cache counter, or external metrics backend is required.

## Performance

Observability adds zero database queries and zero Model hydration.

Cache-hit and cache-miss query budgets remain zero and one.

Generation rotation database queries remain unchanged.

## Planned implementation

Phase 95B may modify only:

- Export Service
- History Service
- A focused Phase 95B test

It must not change the Controller, View, Routes, database, or migrations.

## Compatibility

Summary payload, filters, TTL, generation strategy, administration responses, history behavior, exports, schema, and Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 95B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Observability.
