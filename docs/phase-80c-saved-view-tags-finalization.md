# Phase 80C — Finalize Saved View Tags

## Baseline

- Phase 80B.
- Stable commit: `4e7cbf2`.
- Full suite:
  `1637 passed / 15050 assertions`.
- Phase 80B migrations applied locally.
- Workflow: direct `main` only.

## Finalized database and model contract

The final schema uses:

- `report_saved_view_tags`;
- `report_saved_view_tag`;
- user-scoped normalized tag names;
- optional validated hexadecimal colors;
- cascading assignment removal.

`ReportSavedView` exposes a many-to-many `tags()` relation and
`ReportSavedViewTag` exposes the inverse saved-view relation.

## Finalized ownership contract

Tag creation, update, deletion, single-view synchronization, bulk attachment,
and bulk removal are authenticated-user scoped.

Foreign tag and saved-view identifiers cannot modify another user's data.

## Finalized management contract

The management page supports filtering by any selected tag while preserving
the existing active, archived, and all status modes.

The interface exposes:

- tag filtering;
- tag creation and badges;
- individual saved-view tag synchronization;
- bulk tag attachment;
- bulk tag removal.

## Finalized lifecycle contract

- duplicating an active saved view copies its tag assignments;
- archiving preserves assignments;
- restoring preserves assignments;
- permanent deletion removes pivot assignments;
- deleting a tag removes its assignments without deleting saved views.

## CSV boundary

The CSV schema, format version, writer, and parser remain unchanged.

Tags are not exported. Imported saved views are created without tags.

## Runtime scope

Phase 80C changes no runtime, migration, route, controller, service, model,
view, CSV writer, CSV parser, or database file.

It adds finalization documentation and tests only.

## Next phase

Phase 81A — Select Next Saved View Management Contract.

Workflow: direct `main` only.
