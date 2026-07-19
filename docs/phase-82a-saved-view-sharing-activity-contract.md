# Phase 82A — Prepare Saved View Sharing Activity Contract

## Baseline

- Phase 81C
- Commit `b53de24`
- Full suite: 1682 passed
- Assertions: 15398
- One registered worktree
- Working tree clean

## Scope

Phase 82A is a contract-only phase.

It must not change:

- runtime behavior
- database schema
- migrations
- routes
- controllers
- services
- views
- sharing permissions
- CSV import/export format
- saved-view format version

## Purpose

Define an immutable audit trail for meaningful Saved View Sharing lifecycle events.

The activity feature should answer:

- Who performed the action?
- Which owner and recipient were affected?
- Which saved view and share were involved?
- What action occurred?
- What permission changed?
- When did it occur?
- What minimal source context must survive deletion?

## Proposed storage

Table:

`report_saved_view_share_activities`

Model:

`App\Models\ReportSavedViewShareActivity`

Writer service:

`App\Services\ReportSavedViewShareActivityService`

Activity rows are immutable and contain `created_at` only.

Proposed references use `SET NULL` so the audit row survives deletion of:

- the source saved view
- the share
- the actor
- the owner
- the recipient

Minimal snapshots preserve:

- source name
- source report key

The activity log must not store:

- full filters payload
- sensitive request payload
- IP address by default
- user agent by default

## Locked actions

- `shared`
- `permission_updated`
- `revoked`
- `applied`
- `copied`
- `source_archived`
- `source_restored`
- `source_deleted`

## Write rules

- Write activity only after a successful authorized operation.
- Failed validation and unauthorized actions produce no activity.
- Repeating an identical share produces no activity.
- Repeating a share with a changed permission produces `permission_updated`.
- Activity and business changes must share the same database transaction.
- Activity writes belong to a dedicated service, not ad hoc model writes.
- An activity-write failure must fail the business transaction.

## Read rules

Owner scope:

- Read activity for sources they own.
- Filter by action, recipient, source, and date.

Recipient scope:

- Read activity that directly affects the authenticated recipient.
- Never expose activity for another recipient.

Foreign users receive no access.

Ordering:

- `created_at` descending
- `id` descending as a deterministic tie-breaker

Pagination:

- default 25
- minimum 5
- maximum 100

## Retention rules

- Activity survives share deletion.
- Activity survives source deletion.
- User foreign keys become null after user deletion.
- Snapshot fields preserve minimum human-readable context.
- Existing historical shares are not backfilled.

## Integration boundaries

Phase 82B must not alter:

- `view` and `use` permission semantics
- archive and restoration behavior
- independent-copy behavior
- tag behavior
- CSV import/export schemas
- saved-view format version
- private-by-default behavior

## Phase 82B classification

Phase 82B is large and must be split into validated stages:

1. Database, model, immutable writer service, and foundation tests.
2. Share, permission, revoke, apply, and copy event integration.
3. Archive, restore, and permanent-delete event integration.
4. Owner and recipient activity queries and interfaces.
5. Full regression, documentation, migration, commit, and push.

## Workflow

- Work directly on `main`.
- Do not create or push a phase branch.
- Do not create a Codex worktree.
- Push only `origin/main`.
