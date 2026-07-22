# Phase 96A — Prepare Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics Contract

## Baseline

- Phase: Phase 95C
- Commit: `a395b4ece09c3bc89a2adedb053bf398c56a1677`
- Full suite: 1942 passed
- Assertions: 17747
- Working tree: clean
- Registered worktrees: one

## Classification

Phase 96A is documentation and tests only.

It introduces no runtime, database, migration, model, service, controller, route, or view changes.

## Purpose

Define a bounded, privacy-safe diagnostic snapshot for Summary cache configuration and generation availability.

The diagnostic snapshot must not compute the Summary, execute database queries, or hydrate Models.

## Planned method

The planned Export Service method is:

`summaryCacheDiagnostics()`

It returns a plain array.

## Return shape

The diagnostic payload includes:

- Cache-key prefix
- Summary TTL
- Generation-key prefix
- Generation TTL
- Whether a generation exists
- Generation source: cache, default, or fallback
- Cache store name
- Whether cache reads are available
- Whether observability is enabled

## Behavior

The method reads the generation token at most once.

A missing token reports the default source.

A cache-read failure reports the fallback source and does not throw.

The result size is constant.

## Privacy

The payload must not expose:

- Raw generation token
- Raw cache key
- Raw filters
- Actor user ID
- History payload
- Exception message
- Stack trace

## Access

Phase 96B adds only a public Service method.

No Controller endpoint, Route, View, or authorization change is required.

## Performance

- Maximum cache reads: one
- Maximum database queries: zero
- Maximum Model hydration: zero

## Planned implementation

Phase 96B may modify only:

- Export Service
- A focused Phase 96B test

It must not change the History Service, Controller, View, Routes, database, or migrations.

## Compatibility

Summary payload, filters, TTL, generation strategy, observability events, administration responses, history behavior, exports, schema, and Model remain unchanged.

## Workflow

- Branch: `main`
- Push target: `origin/main`
- Full suite: once before commit
- Post-commit full suite: not permitted
- Successful phase: commit and push immediately

## Next recommendation

Phase 96B — Implement Saved View Sharing Activity Retention Execution History Export Summary Cache Diagnostics.
