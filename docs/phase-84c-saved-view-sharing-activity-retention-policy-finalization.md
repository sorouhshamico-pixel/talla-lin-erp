# Phase 84C — Finalize Saved View Sharing Activity Retention Policy

## Baseline

- Phase 84B
- Commit `fe41d7d`
- Full suite: 1759 passed
- Assertions: 15881
- Working tree clean
- One registered worktree

## Finalized capability

Saved View Sharing Activity Retention is complete and locked.

The implementation includes:

- retention service
- Artisan pruning command
- configuration
- explicit command registration
- conditional scheduler registration
- dry-run support
- chunked transactional deletion
- execution observability

## Default policy

The default policy retains activity forever.

Automatic pruning remains disabled until explicitly configured.

## Retention limits

- minimum retention period: 30 days
- maximum retention period: 3650 days
- minimum chunk size: 1
- maximum chunk size: 10000
- default chunk size: 500

## Eligibility

Only rows whose `created_at` value is strictly older than the cutoff
are eligible.

Rows exactly at the cutoff remain.

Future-dated rows remain.

## Command

Command:

`reports:prune-saved-view-share-activities`

Options:

- `--days=`
- `--dry-run`
- `--chunk=`

The command accepts numeric values supplied directly by CLI or through
programmatic `Artisan::call()` execution.

## Scheduler

Scheduler registration is conditional on configuration.

Supported schedules:

- hourly
- daily
- weekly
- monthly

Unknown configured values fall back to daily.

## Execution safety

- dry-run does not delete
- deletion uses query builder
- deletion is chunked
- every chunk uses a transaction
- unbounded mass deletion is forbidden
- pruning creates no activity rows

## Observability

Execution reports:

- candidate count
- deleted count
- cutoff
- duration

Success and failure are written to the application log.

## Immutability boundary

Normal model updates and deletes remain forbidden.

Retention query-builder deletion is the only defined policy exception.

## Compatibility boundaries

Phase 84 does not change:

- activity schema
- activity actions
- activity export
- sharing permissions
- saved-view CSV
- saved-view format version

## Workflow policy

- Work directly on `main`.
- Run the full suite once before commit.
- Do not repeat the full suite after commit.
- Push every successful phase immediately.
- Use each pushed commit as the next baseline.

## Next recommendation

Phase 85A — Prepare Saved View Sharing Activity Retention Administration Contract.
