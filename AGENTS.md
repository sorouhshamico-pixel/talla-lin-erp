# AGENTS.md

## Project identity

- Project: Talla Lin ERP.
- Framework: Laravel / PHP.
- Local repository: `C:\laragon\www\talla-lin-erp`.
- Stable and development branch: `main`.
- GitHub remote:
  `https://github.com/sorouhshamico-pixel/talla-lin-erp.git`.
- Communicate progress and results to the user in Arabic.

## Main-only workflow

All future phases are implemented directly in the primary repository on
`main`.

Do not create:

- Codex worktrees;
- `agents/*` branches;
- `phase/*` branches;
- temporary remote development branches.

Only push completed, fully validated commits to `origin/main`.

## Mandatory startup audit

Before editing:

```bash
cd /c/laragon/www/talla-lin-erp
git fetch --prune origin
git status --short
git branch --show-current
git rev-parse --short HEAD
git rev-parse --short origin/main
git log -5 --oneline --decorate
git worktree list
```

Requirements:

- current branch is `main`;
- local `main` equals `origin/main`;
- working tree is clean;
- only the primary worktree is registered.

Stop if any requirement is not satisfied.

## Safety rules

1. Never use force push.
2. Never reset, clean, restore, or discard uncommitted work automatically.
3. Never commit or push failing code.
4. Never suppress, skip, or weaken a valid test to claim success.
5. Keep one completed phase in one commit.
6. Do not mix unrelated changes.
7. Do not create or push a phase branch.
8. Do not create a Codex worktree.
9. Stop on unexpected Git divergence, failed tests, syntax errors, or push
   failure.
10. Preserve historical contract documents.

## Phase execution protocol

### 1. Confirm baseline

Run the full suite before changing code:

```bash
php artisan test
```

Do not continue if it fails.

### 2. Inspect and plan

Read the relevant runtime files, previous phase documents, current tests,
routes, services, controllers, models, and views.

Define:

- exact objective;
- exact file scope;
- preserved behavior;
- focused tests;
- commit message.

### 3. Implement narrowly

Change only the minimum required files. Avoid unrelated refactoring and broad
search-and-replace operations.

### 4. Static validation

Run `php -l` for every changed PHP file and `git diff --check`.

### 5. Focused tests

Run the new phase test first, followed by directly related regression tests.

### 6. Full suite before commit

Run:

```bash
php artisan test
```

No commit is allowed unless it passes.

### 7. Review exact scope

Run:

```bash
git status --short
git diff --check
git diff --stat
git diff
```

Confirm there are no accidental files, debug output, secrets, generated
artifacts, or unrelated changes.

### 8. Documentation

For every phase:

- add the phase JSON and Markdown document;
- add or update the phase test;
- update `docs/codex-development-log.md`;
- update `docs/codex-handoff.md`;
- update `docs/CODEX_PROJECT_STATE.md`.

### 9. Commit directly on main

Create one intentional commit on `main`.

After committing, run the focused phase test and the full test suite again.

### 10. Push only main

Fetch and confirm `origin/main` has not moved since the baseline. Then run:

```bash
git push origin main
git fetch --prune origin
```

Verify:

```bash
git rev-parse --short HEAD
git rev-parse --short origin/main
git status --short
git worktree list
```

Success requires:

- local `main` equals `origin/main`;
- working tree is clean;
- only the primary worktree exists;
- no `agents/codex-*` remote branch exists.

## Failure protocol

When any command fails:

1. stop immediately;
2. do not push;
3. keep the current uncommitted or local committed state intact;
4. report the exact command and error;
5. inspect the actual file and root cause;
6. apply one narrow correction;
7. rerun the failed test;
8. rerun focused tests;
9. rerun the full suite.

Do not create a backup branch automatically. Ask the user before any
destructive recovery action.

## Current stable state

- Phase: Phase 81A — Prepare Saved View Sharing Contract.
- Commit: the commit containing this file.
- Baseline: Phase 80C at `7a75448`.
- Historical contract:
  Phase 80A — Prepare Saved View Tags Contract.
- Historical implementation:
  Phase 80B — Implement Saved View Tags.
- Historical finalization:
  Phase 80C — Finalize Saved View Tags.
- Validated baseline full suite:
  `1643 passed / 15126 assertions`.
- Next phase:
  Phase 81B — Implement Saved View Sharing.
- Large-phase policy:
  split Phase 81B into small validated stages.

## Phase 81B — Saved View Sharing

- Phase 81B implements owner-scoped sharing for report saved views.
- The sharing table is `report_saved_view_shares`.
- Supported permissions are `view` and `use`.
- Only the owner may create, update, revoke, or list recipients.
- Recipients may list received active shares, apply a `use` share, and copy a share into an independent saved view.
- Recipient copies are active, non-default, private, and do not inherit owner tags or shares.
- Archived sources remain shared in storage but are hidden and cannot be applied or copied until restored.
- CSV schemas, parser behavior, writer behavior, and format versions remain unchanged.
- Existing saved views and imported rows remain private by default.
- Phase 81B was implemented as five validated stages because it is a large phase.

## Phase 81C — Finalize Saved View Sharing

- Phase 81C finalizes and locks the Saved View Sharing implementation from Phase 81B.
- This phase is documentation and tests only.
- Do not change runtime behavior, database schema, migrations, routes, views, CSV format, import/export format version, or permission semantics.
- The locked sharing permissions remain `view` and `use`.
- Archived source views preserve share rows but block recipient listing, apply, and copy until restoration.
- Recipient copies remain independent, active, non-default, and do not inherit owner tags or source shares.
- Continue the main-only workflow and push only `origin/main`.

## Phase 82B — Saved View Sharing Activity

Phase 82B is finalized.

Baseline implementation commits:

- Stage 3: `14da213`
- Stage 4: `f719301`

The implementation includes immutable sharing activity records, eight
locked actions, lifecycle retention, owner-scoped history, recipient-scoped
history, filtering, pagination, and HTML/JSON interfaces.

Workflow policy:

- run the full suite once before commit
- do not repeat the full suite after commit
- push every successful phase directly to `origin/main`
- use each successful pushed phase as the next baseline

Next recommended phase:

Phase 83A — Prepare Saved View Sharing Activity Export Contract.

## Phase 83C — Sharing Activity Export Finalized

Phase 83 is complete.

Baseline implementation commit: `5b56257`.

The implementation includes owner-scoped and recipient-scoped streamed
CSV exports, deterministic filtering and ordering, UTF-8 BOM output,
15 locked columns, and strict metadata boundaries.

Next recommended phase:

Phase 84A — Prepare Saved View Sharing Activity Retention Policy Contract.

## Phase 84C — Sharing Activity Retention Finalized

Phase 84 is complete.

Baseline implementation commit: `fe41d7d`.

The implementation includes explicit retention configuration, dry-run,
chunked transactional pruning, command registration, conditional scheduler
registration, and execution observability.

Next recommended phase:

Phase 85A — Prepare Saved View Sharing Activity Retention Administration Contract.

## Phase 85C — Retention Administration Finalized

Phase 85 is complete.

Baseline implementation commit: `fd7fbe3`.

The implementation includes owner-gated retention administration,
HTML and JSON status interfaces, manual preview, guarded execution,
concurrency locking, and audit context.

Next recommended phase:

Phase 86A — Prepare Saved View Sharing Activity Retention Execution History Contract.

## Phase 86C — Retention Execution History Finalized

Phase 86 is complete at implementation baseline `eea711b`.

Next: Phase 87A — Prepare Saved View Sharing Activity Retention Execution History Export Contract.
