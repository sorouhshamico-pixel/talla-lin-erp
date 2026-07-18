# Phase 80B — Implement Saved View Tags

## Baseline

- Phase 80A.
- Stable starting commit: `a999163`.
- Starting full suite: `1625 passed / 14988 assertions`.
- Workflow: direct `main` only.

## Implemented

- user-scoped tags with normalized names;
- optional validated hexadecimal colors;
- many-to-many saved-view tag assignments;
- tag creation, update, and deletion;
- per-view tag synchronization;
- bulk tag attach and detach;
- management filtering by any selected tag;
- active, archived, and all status modes;
- duplicate copies tag assignments;
- archive and restore preserve assignments;
- permanent saved-view deletion cascades pivot rows;
- management tag filter, manager, badges, and assignment controls.

## CSV boundary

- CSV schema remains unchanged.
- CSV format version remains unchanged.
- CSV writer remains unchanged.
- CSV parser remains unchanged.
- Tags are not exported.
- Imported saved views remain untagged.

## Validation

Pre-commit full suite:

`1637 passed / 15050 assertions`

## Next phase

Phase 80C — Finalize Saved View Tags.
