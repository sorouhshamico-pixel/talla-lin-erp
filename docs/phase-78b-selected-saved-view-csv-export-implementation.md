# Phase 78B — Implement Selected Saved View CSV Export

## Baseline

- Phase 78A.
- Stable commit: `18860e1`.
- Full suite:
  `1577 passed / 14457 assertions`.

## Implemented behavior

An authenticated POST endpoint now exports only the selected saved views.

The service normalizes positive unique IDs, applies authenticated-user scope,
ignores foreign and missing IDs without disclosure, and returns deterministic
management ordering.

The controller formats the selected rows and delegates CSV bytes to the
existing final writer. The writer itself is unchanged.

A valid selection containing no owned rows returns a header-only CSV.

## Management page

The existing row checkboxes, select-all control, and selected counter are
reused.

The bulk-selection form exports by default. The delete button targets the
existing bulk-delete route and submits `_method=DELETE` only when that button
is clicked. Delete confirmation is scoped to the delete button.

## Preserved behavior

- filtered CSV export;
- bulk deletion;
- delete all;
- import preview and apply;
- CSV schema and version;
- export/import round trip;
- authenticated-user isolation.

## Next phase

Phase 78C — Finalize Selected Saved View CSV Export.
