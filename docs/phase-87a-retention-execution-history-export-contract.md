# Phase 87A — Prepare Saved View Sharing Activity Retention Execution History Export Contract

## Baseline

- Phase 86C
- Commit `edcbcab`
- Full suite: 1802 passed
- Assertions: 16251
- Working tree clean
- One registered worktree

## Scope

Phase 87A is contract-only.

It introduces no runtime, database, migration, model, service, controller,
route, or view changes.

## Purpose

Provide controlled export of retention execution history for operational review.

## Authorization

Export requires:

- authentication
- permission `manage_saved_view_share_activity_retention`

## Formats

Supported formats:

- CSV
- JSON

Default format:

CSV

## Filters

Exports accept the same filters as the history read interface:

- type
- status
- actor user ID
- started from
- started to

## Ordering

- `created_at desc`
- `id desc`

## Exported columns

- id
- type
- status
- actor user ID
- requested retention days
- requested chunk size
- candidate count
- deleted count
- cutoff timestamp
- duration
- failure class
- failure message
- started timestamp
- finished timestamp
- created timestamp

## Excluded fields

The export must not include:

- context
- updated timestamp
- full activity metadata
- filters payload
- credentials
- secrets

## CSV contract

CSV export requires:

- UTF-8 BOM
- comma delimiter
- CRLF line endings
- streaming response
- filename prefix `saved-view-retention-execution-history`
- maximum 100000 rows

## JSON contract

JSON export includes:

- exported timestamp
- applied filters
- item count
- items

Maximum JSON rows:

10000

## Audit

Each export request logs:

- actor user ID
- format
- filters
- exported count
- duration

An export must not create:

- retention execution-history rows
- sharing activity rows

## Error responses

- forbidden: 403
- validation error: 422
- row limit exceeded: 422

## Compatibility boundaries

Phase 87 must not change:

- execution-history table
- execution-history model
- history writer
- history read route
- retention policy
- retention administration
- retention command signature
- scheduler contract
- sharing activity schema
- sharing activity export

## Next phase

Phase 87B — Implement Saved View Sharing Activity Retention Execution History Export.
