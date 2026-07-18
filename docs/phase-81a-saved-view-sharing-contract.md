# Phase 81A — Prepare Saved View Sharing Contract

## Selection decision

The selected capability is saved-view sharing.

Sharing was deferred during the tags selection phase. Tags are now implemented
and finalized, so sharing is the next high-value saved-view management
capability.

Manual sorting, folders, and broader team permissions remain deferred.

## Baseline

- Phase 80C.
- Stable commit: `7a75448`.
- Full suite:
  `1643 passed / 15126 assertions`.
- Workflow: direct `main` only.

## Scope

Phase 81A changes no runtime, migration, route, controller, model, service,
view, CSV writer, CSV parser, or database file.

Implementation is reserved for Phase 81B. Finalization is reserved for
Phase 81C.

## Sharing model

Each share connects:

- one saved view;
- its owner;
- one recipient;
- a permission value of `view` or `use`.

A saved view can be shared only once with the same recipient.

Existing saved views remain private.

## Ownership boundary

Only the owner can:

- create a share;
- change permission;
- revoke a share;
- list recipients.

Recipients cannot mutate, archive, delete, tag, rename, default, or reshare the
source saved view.

Self-sharing is rejected.

## Recipient behavior

Recipients can list received shares.

The `view` permission permits viewing metadata only.

The `use` permission permits applying the active shared view.

A recipient can copy an accessible shared view into their own account. The copy
is independent, active, and non-default. It does not inherit owner tags or
shares.

## Archive behavior

Archiving preserves share records but suspends recipient access.

Restoring reactivates existing shares.

Permanent deletion removes all share records.

## Tags and CSV boundary

Owner tags remain private and are not copied to recipients.

CSV schema, format version, writer, and parser remain unchanged.

Shares are not exported. Imports create private unshared saved views.

## Phase 81B execution plan

Phase 81B is expected to be large and must be divided into small stages:

1. database, models, relations, and ownership service;
2. routes, controllers, and authorization;
3. owner and recipient management interfaces;
4. archive, tags, duplicate, and CSV boundary integration;
5. full validation, documentation, migration, commit, and push.

## Next phase

Phase 81B — Implement Saved View Sharing.

Workflow: direct `main` only.
