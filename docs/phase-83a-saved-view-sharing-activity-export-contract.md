# Phase 83A — Prepare Saved View Sharing Activity Export Contract

## Baseline

- Phase 82B Stage 5
- Commit `f1fb3ac`
- Full suite: 1725 passed
- Assertions: 15663
- Working tree clean
- One registered worktree

## Scope

Phase 83A is contract-only.

It must not change:

- runtime behavior
- database schema
- migrations
- routes
- controllers
- services
- views
- sharing permissions
- saved-view CSV import/export
- saved-view format version

## Export audiences

Two independent authenticated exports are required.

### Owner export

The authenticated user may export only rows whose `owner_user_id`
matches the authenticated user.

The owner export supports:

- action
- recipient user
- source saved view
- date from
- date to

### Recipient export

The authenticated user may export only rows whose
`recipient_user_id` matches the authenticated user.

The recipient export supports:

- action
- source saved view
- date from
- date to

The export filter semantics must match the existing activity-history
query semantics.

## CSV contract

The only format in Phase 83B is CSV.

Required behavior:

- UTF-8 with BOM
- comma delimiter
- LF line endings
- streamed response
- deterministic descending activity order
- empty export allowed
- no unbounded collection loading

Required columns:

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

## Deleted-reference behavior

Foreign IDs may be empty after related records are deleted.

Snapshot values must remain usable:

- source name
- source report key

Missing user names are exported as empty values.

## Metadata policy

The complete metadata object must not be exported.

Only `copied_saved_view_id` may be extracted into its dedicated column.

The saved-view filters payload must not be exported.

## Authorization

- Authentication is mandatory.
- Owner and recipient exports are separate.
- Cross-user exports are forbidden.
- Empty authorized exports are valid.
- Export authorization must not depend on client-provided user IDs.

## Planned implementation

Phase 83B should add:

- `ReportSavedViewShareActivityExportService`
- `ReportSavedViewShareActivityExportController`
- owner export route
- recipient export route
- implementation tests

## Execution classification

Phase 83B is medium and should execute as one validated stage.

## Workflow policy

- Work directly on `main`.
- Run the full suite once before commit.
- Do not repeat the full suite after commit.
- Push every successful phase immediately to `origin/main`.
- Use the pushed commit as the next baseline.
