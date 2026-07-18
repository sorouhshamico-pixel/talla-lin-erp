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

- Phase: Phase 80C — Finalize Saved View Tags.
- Commit: the commit containing this file.
- Baseline: Phase 80B at `4e7cbf2`.
- Historical contract:
  Phase 80A — Prepare Saved View Tags Contract.
- Historical implementation:
  Phase 80B — Implement Saved View Tags.
- Validated full suite:
  `1643 passed / 15126 assertions`.
- Next phase:
  Phase 81A — Select Next Saved View Management Contract.
