# AGENTS.md

## Project identity

- Project: Talla Lin ERP
- Framework: Laravel / PHP
- Local repository: `C:\laragon\www\talla-lin-erp`
- Git branch used for stable releases: `main`
- GitHub remote:
  `https://github.com/sorouhshamico-pixel/talla-lin-erp.git`
- Communicate progress and results to the user in Arabic.
- Keep code, class names, commit messages, commands, and file paths in their
  natural technical language.

## Current confirmed baseline

The last confirmed successful stable state is:

- Phase: `Phase 77A — Prepare Saved View CSV Export Writer Contract`
- Commit: `0b47c18`
- Full test suite: `1559 passed`
- Assertions: `14194`
- Remote `origin/main` was confirmed at `0b47c18`.

The next intended phase is:

- `Phase 77B — Implement Saved View CSV Export Writer`

Do not assume the local working tree is currently clean. Inspect it first.

## Mandatory startup audit

Before editing any file, run and report:

```bash
pwd
git status --short
git branch --show-current
git remote -v
git log -5 --oneline --decorate
git diff --stat
git ls-files --others --exclude-standard
```

Also run:

```bash
git fetch origin
git rev-parse --short HEAD
git rev-parse --short origin/main
```

Do not make assumptions based only on this document. The repository and
command output are the source of truth.

## Safety rules

1. Never discard, reset, clean, or overwrite uncommitted work before creating
   a recoverable backup.
2. Never use `git push --force` or `git push --force-with-lease`.
3. Never delete backup branches.
4. Never hide or suppress a failed test.
5. Never weaken a valid runtime assertion merely to make a test pass.
6. Never commit or push broken code to `main`.
7. Never mix multiple development phases in one commit.
8. Never run old generated `phase77b_*.sh` scripts. Inspect and edit the
   repository directly.
9. Do not perform broad regex rewrites across all tests. Update only verified
   files and preferably replace complete test methods when source ownership
   changes.
10. Stop at the first unexpected failure, diagnose its root cause, and report
    the exact file, line, command, and error before making another change.

## Preserving a dirty starting state

When the working tree is dirty and the user has authorized returning to a
known stable baseline:

1. Record the current status and diff.
2. Create a timestamped backup branch.
3. Commit all current tracked and untracked project changes to that backup
   branch.
4. Push the backup branch to GitHub.
5. Return to `main`.
6. Fetch `origin`.
7. Restore `main` only after confirming the backup commit and push succeeded.
8. Verify the restored working tree is clean.

Suggested naming:

```text
backup/codex-preflight-YYYYMMDD-HHMMSS
```

A reset is permitted only after the backup branch exists locally and remotely.

## Branch workflow for every phase

Use a dedicated phase branch created from the confirmed clean `main`:

```text
phase/<phase-number>-<short-description>
```

Example:

```text
phase/77b-saved-view-csv-export-writer
```

Do not implement a phase directly on `main`.

After the phase passes all checks:

1. Commit on the phase branch.
2. Push the phase branch.
3. Re-run the required tests on the committed branch.
4. Switch to `main`.
5. Fetch `origin`.
6. Confirm `main` and `origin/main` have not moved unexpectedly.
7. Fast-forward merge the phase branch into `main`.
8. Run the full test suite again on `main`.
9. Push `main`.
10. Verify `origin/main` equals the new commit.
11. Verify `git status --short` is empty.

If fast-forward merge is not possible, stop and report. Do not create an
unreviewed merge commit.

## One-phase execution protocol

For every phase, follow this exact sequence.

### 1. Establish the baseline

Report:

- current branch;
- local HEAD;
- remote HEAD;
- working-tree status;
- previous phase and commit;
- previous confirmed full-suite totals.

Run the existing full suite before implementation when restoring a baseline or
when the current state is uncertain:

```bash
php artisan test
```

Do not continue if the baseline test suite fails.

### 2. Inspect before planning

Read:

- the relevant production files;
- the previous phase contract and implementation documents;
- the relevant focused tests;
- historical tests that assert source ownership;
- routes, services, models, and views touched by the behavior.

Search for every source marker that will move between classes.

### 3. Present the plan

Before editing, give the user a concise plan containing:

- objective;
- runtime files to create or edit;
- tests to add or edit;
- documentation files to add;
- behavior that must remain unchanged;
- focused-test list;
- expected commit message.

Do not begin a second phase in the same run.

### 4. Implement narrowly

- Edit the smallest valid set of files.
- Prefer direct file edits over generated shell scripts.
- Preserve established Laravel conventions and formatting.
- Do not introduce unrelated refactors.
- Do not change public behavior unless the phase explicitly requires it.
- Keep old contract documents as historical records.
- When source ownership moves, update only tests that explicitly assert the
  old owner.

### 5. Static validation

Run PHP syntax checks for every changed PHP file:

```bash
php -l path/to/changed-file.php
```

Run available formatting or static-analysis commands if the repository already
uses them. Do not install new dependencies without explicit user approval.

### 6. Focused tests

Run the new phase test first, then the immediately related regression tests.

For Saved View phases, include relevant tests from:

- the current phase;
- the previous phase;
- CSV export;
- filters payload;
- format version;
- version registry;
- CSV parser;
- import apply service;
- saved-view management;
- saved-view editing.

Report every command and exact pass/fail totals.

### 7. Full test suite

Run:

```bash
php artisan test
```

No commit or push is permitted unless the complete suite passes.

### 8. Review before commit

Run:

```bash
git status --short
git diff --check
git diff --stat
git diff
```

Review for:

- accidental files;
- debug output;
- duplicate imports;
- syntax errors;
- stale source assertions;
- broad or unrelated changes;
- generated artifacts that should not be committed;
- secrets or local paths.

Use Codex review mode when available to review uncommitted changes.

### 9. Documentation

For every successful phase, update both:

```text
docs/codex-development-log.md
docs/codex-handoff.md
```

Append to `docs/codex-development-log.md`:

- date and time;
- phase and title;
- baseline commit;
- phase branch;
- objective;
- production files changed;
- tests changed or added;
- documentation changed or added;
- focused-test commands and totals;
- full-suite totals;
- commit hash;
- push result;
- next phase;
- known risks or none.

Overwrite `docs/codex-handoff.md` with the latest exact state:

- current stable branch;
- current stable commit;
- current remote commit;
- latest successful phase;
- exact full-suite totals;
- clean/dirty status;
- backup branches;
- next phase;
- files added or changed;
- decisions that must be preserved;
- exact first commands for the next Codex session.

Documentation must be committed with the phase.

### 10. Commit and GitHub publication

Use one intentional commit per completed phase.

Commit-message pattern:

```text
<Verb> <saved-view feature or phase objective>
```

Examples:

```text
Implement saved view CSV export writer
Finalize saved view CSV export writer
```

Push the phase branch first. Merge and push `main` only after all tests pass.

After pushing, verify:

```bash
git rev-parse --short HEAD
git rev-parse --short origin/main
git status --short
```

Report the actual hashes and test totals. Never claim success without command
evidence.

## Phase 77B requirements

Implement:

```text
App\Support\Reports\ReportSavedViewCsvExportWriter
```

Required boundary:

- final class;
- stateless;
- no constructor dependencies;
- public API:

```php
public function write(iterable $formattedSavedViews): void
```

The writer owns:

- opening and closing `php://output`;
- UTF-8 BOM;
- registry export header;
- current format version;
- filter count;
- human-readable filter summary;
- machine-readable filters payload;
- CSV row serialization;
- input order preservation.

The controller retains:

- request validation;
- report-key normalization;
- authenticated-user query;
- search and report filters;
- `formatSavedView`;
- filename;
- `StreamedResponse`;
- `Content-Type`.

Runtime behavior must remain unchanged.

For historical source-marker tests:

- inspect the actual baseline file first;
- replace complete source-ownership test methods;
- do not perform fragile string substitutions inside quoted PHP marker arrays;
- update only Phase 69, 72, 73, 74, and the historical Phase 77A baseline tests
  that explicitly assert export ownership;
- do not modify Phase 75 parser ownership or Phase 76 import-apply ownership
  unless an actual failing assertion proves it is necessary.

Use supported scalar filters for controller export/import round-trip tests:

```php
'payment_status' => 'partial',
'aging_bucket' => 'overdue_61_90',
```

Nested values may be tested directly against the writer without passing them
through the controller's display formatting.

## Failure protocol

When a command fails:

1. Stop the phase pipeline.
2. Do not commit or push to `main`.
3. Preserve all changes on the phase branch.
4. Show the failing command.
5. Show the exact failure and relevant stack trace.
6. Identify whether the defect is runtime code, test design, historical source
   assertion, environment, or repository state.
7. Inspect the actual file before editing.
8. Apply one root-cause fix.
9. Re-run the failed test first.
10. Re-run the complete focused set.
11. Re-run the full suite.

Do not produce a chain of speculative repair scripts.

## Context and token management

Codex may show a context-left indicator in the interactive interface, but do
not rely on exact token forecasting.

At the end of every phase, always create a complete Git and documentation
checkpoint before starting another phase.

Do not start a new phase when:

- the context-left indicator is at or below 25%;
- the session has accumulated several large test logs;
- the next phase cannot reasonably be completed, tested, documented, committed,
  and pushed in the remaining context.

When context is low:

1. Finish only the current safe unit if possible.
2. Run the required tests.
3. Commit and push only if the phase is fully valid.
4. If incomplete, commit the work only to a clearly named WIP backup branch and
   push that branch; never merge it to `main`.
5. Update `docs/codex-handoff.md`.
6. Tell the user explicitly that a new Codex session is required.
7. Provide the exact next prompt and first commands for the new session.

Never begin another phase silently when context is low.

## Automatic phase continuation

After a phase is fully completed, tested, documented, committed, merged into main, pushed to GitHub, and verified clean, automatically begin the next documented phase without waiting for user confirmation.

At the end of every successful phase:
- show a concise Arabic checkpoint report;
- update docs/codex-development-log.md;
- update docs/codex-handoff.md;
- commit and push the completed phase;
- verify origin/main equals the local main commit;
- verify the working tree is clean;
- then immediately inspect and begin the next phase.

Continue automatically through successive phases.

Stop automatic execution only when one of these conditions occurs:
- any focused or full test fails;
- PHP syntax validation fails;
- git diff --check fails;
- unrelated user changes are detected;
- local main and origin/main diverge unexpectedly;
- a backup, reset, clean, force operation, merge conflict, or destructive action requires a decision;
- push to GitHub fails;
- requirements for the next phase are ambiguous;
- context remaining is 25% or less;
- the current phase cannot be fully tested, documented, committed, and pushed safely.

When stopped:
- preserve all work on a named branch;
- do not merge broken or incomplete work into main;
- update docs/codex-handoff.md;
- explain the exact reason in Arabic;
- provide the exact next action required.

Do not pause merely to ask whether to start the next phase.
