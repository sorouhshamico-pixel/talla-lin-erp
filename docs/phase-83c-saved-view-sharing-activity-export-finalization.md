# Phase 83C — Finalize Saved View Sharing Activity Export

## Baseline

- Phase 83B
- Commit `5b56257`
- Full suite: 1739 passed
- Assertions: 15745
- Working tree clean
- One registered worktree

## Finalized capability

Saved View Sharing Activity Export is complete and locked.

The implementation provides two separate authenticated CSV exports:

- owner activity export
- recipient activity export

## Locked behavior

- CSV only
- UTF-8 with BOM
- comma delimiter
- LF line endings
- streamed download response
- cursor-based iteration
- deterministic descending order
- empty authorized exports allowed

## Locked authorization

Owner export is restricted to activity rows whose `owner_user_id`
matches the authenticated user.

Recipient export is restricted to activity rows whose
`recipient_user_id` matches the authenticated user.

Client-provided user IDs do not control export scope.

## Locked filters

Owner export:

- action
- recipient user
- source saved view
- date from
- date to

Recipient export:

- action
- source saved view
- date from
- date to

## Locked columns

1. `activity_id`
2. `created_at`
3. `action`
4. `source_saved_view_id`
5. `source_name`
6. `source_report_key`
7. `actor_user_id`
8. `actor_name`
9. `owner_user_id`
10. `owner_name`
11. `recipient_user_id`
12. `recipient_name`
13. `permission_before`
14. `permission_after`
15. `copied_saved_view_id`

## Locked metadata policy

The full metadata payload is not exported.

The saved-view filters payload is not exported.

Only `copied_saved_view_id` is extracted into a dedicated column.

## Deleted-reference behavior

Deleted foreign references may produce empty IDs or names.

Source snapshots remain available:

- source name
- source report key

## Compatibility boundaries

Phase 83 does not change:

- activity schema
- activity actions
- sharing permissions
- saved-view CSV format
- saved-view format version

## Workflow policy

- Work directly on `main`.
- Run the full suite once before commit.
- Do not repeat the full suite after commit.
- Push every successful phase immediately.
- Use each pushed commit as the next baseline.

## Next recommendation

Phase 84A — Prepare Saved View Sharing Activity Retention Policy Contract.
