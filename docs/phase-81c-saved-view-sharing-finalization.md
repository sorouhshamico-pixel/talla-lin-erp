# Phase 81C — Finalize Saved View Sharing

## Baseline

- Phase 81B
- Commit `d44cf27`
- Full suite: 1676 passed
- Assertions: 15317
- Migration applied
- One registered worktree
- Working tree clean

## Finalization scope

Phase 81C is documentation and locking tests only.

It must not change:

- runtime behavior
- database schema
- migrations
- routes
- views
- CSV format
- import or export format version
- sharing permission semantics

## Locked sharing contract

The sharing table is `report_saved_view_shares`.

Supported permissions:

- `view`
- `use`

Owner operations:

- list recipients
- create or idempotently update a share
- update permission
- revoke a share

Recipient operations:

- list received shares whose source is active
- apply a share only with `use`
- copy an active shared view into an independent private saved view

## Ownership boundaries

- Recipients cannot mutate the owner's source.
- Foreign users cannot manage a share.
- Self-sharing is rejected.
- Source ownership remains unchanged.

## Archive and deletion boundaries

- Archiving preserves the share row.
- Archived sources are hidden from recipient listings.
- Apply and copy are blocked while archived.
- Restoring the source reactivates recipient access.
- Permanent source deletion cascades to share rows.

## Copy boundaries

A copied shared view is:

- owned by the recipient
- active
- non-default
- independent from the source

The copy does not inherit:

- owner tags
- source shares

## CSV and import/export boundaries

- No sharing columns are added.
- The import/export format version does not change.
- New and imported saved views remain private by default.

## Workflow lock

- Work directly on `main`.
- Do not create or push a phase branch.
- Do not create a Codex worktree.
- Push only `origin/main`.

## Next recommendation

Phase 82A — Prepare Saved View Sharing Activity Contract.
