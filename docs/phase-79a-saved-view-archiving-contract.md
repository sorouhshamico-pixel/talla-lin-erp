# Phase 79A — Prepare Saved View Archiving Contract

## Selection decision

The next saved-view management capability is reversible archiving.

Saved views currently support permanent deletion, including single, selected,
and delete-all operations. They do not have a reversible inactive lifecycle
state. Archiving therefore provides immediate management value with less
structural risk than sharing, tags, or manual sorting.

## Baseline

- Phase 78C.
- Stable commit: `5c3def2`.
- Full suite:
  `1595 passed / 14620 assertions`.
- Workflow: direct `main` only.

## Phase 79A boundary

This phase is contract-only:

- no runtime changes;
- no migration yet;
- no database mutation;
- no route or view changes;
- no CSV schema change.

## Phase 79B database contract

Add nullable `archived_at` to `report_saved_views`.

Existing rows remain active because the new value is null. Add an index on
`user_id` and `archived_at`.

The model adds a datetime cast and active/archived helpers.

## Management status contract

The management page accepts:

```text
status=active
status=archived
status=all
```

Default is `active`.

The status value is preserved through pagination, return queries, and filtered
CSV export.

## Lifecycle contract

Archiving:

- is authenticated-user scoped;
- sets `archived_at`;
- clears `is_default` atomically;
- is idempotent.

Restoring:

- is authenticated-user scoped;
- clears `archived_at`;
- does not automatically restore default status;
- is idempotent.

Single and bulk archive/restore operations are included.

## Report-facing behavior

Archived saved views are excluded from normal report saved-view lists and
default-view lookup.

Archived rows cannot be applied, edited, duplicated, or made default through
direct routes. They may be restored, permanently deleted, or explicitly
exported from the management page.

## CSV boundary

The CSV schema and format version do not change.

`archived_at` is not exported. Explicit selected export may include archived
rows, and importing those rows creates active saved views.

The existing writer and parser remain unchanged.

## Preserved behavior

- selected and filtered CSV export;
- import preview and apply;
- single, bulk, and delete-all behavior;
- authenticated-user isolation;
- existing active saved-view actions;
- main-only Git workflow.

## Next phase

Phase 79B — Implement Saved View Archiving.

Workflow: direct `main` only.
