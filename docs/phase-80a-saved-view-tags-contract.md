# Phase 80A — Prepare Saved View Tags Contract

## Selection decision

The next saved-view management capability is user-scoped tags.

Tags were deferred during Phase 79 while archiving was implemented. They now
provide the best balance of management value and implementation risk. Sharing
requires a broader permission model, while manual sorting changes list-order
semantics throughout the module.

## Baseline

- Phase 79C.
- Stable commit: `5dbb364`.
- Full suite:
  `1619 passed / 14915 assertions`.
- Workflow: direct `main` only.

## Phase 80A boundary

This phase is contract-only:

- no runtime changes;
- no migration yet;
- no route or view changes;
- no CSV schema or version change.

## Database contract

Phase 80B adds:

- `report_saved_view_tags`, scoped by user;
- `report_saved_view_tag`, the assignment pivot.

Tag names are unique per user after trimming, whitespace normalization, and
case normalization. Existing saved views remain untagged.

## Management contract

Management filtering accepts multiple tag IDs and matches any selected tag.

The tag filter is preserved with status, search, report key, pagination,
return queries, import preview context, and filtered export.

Tags can be assigned to active or archived saved views.

## Lifecycle contract

Archiving and restoring preserve tag assignments. Permanent saved-view
deletion removes pivot assignments through cascade behavior.

Duplicating a saved view copies its tags while the duplicate remains active
and non-default.

## Authorization contract

Tags and saved views remain fully user-scoped. Foreign IDs are ignored or
rejected without disclosing their existence.

Single assignment and bulk attach/detach operations are included.

## CSV boundary

CSV schema and format version do not change.

Tags are not serialized. Filtered export may use the tag filter, but selected
export remains unchanged. Imported rows start without tags.

The writer and parser remain unchanged.

## Preserved behavior

- saved-view archiving and restoration;
- status filtering;
- selected and filtered CSV export;
- import preview and apply;
- permanent delete operations;
- default-view behavior;
- historical source contracts;
- direct `main` workflow.

## Next phase

Phase 80B — Implement Saved View Tags.
