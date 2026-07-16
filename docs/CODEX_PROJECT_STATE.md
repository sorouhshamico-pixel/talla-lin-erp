# Codex Project State — Talla Lin ERP

## Repository

```text
Local path: C:\laragon\www\talla-lin-erp
GitHub: https://github.com/sorouhshamico-pixel/talla-lin-erp.git
Stable branch: main
```

## Last confirmed successful stable state

```text
Phase: Phase 77A — Prepare Saved View CSV Export Writer Contract
Commit: 0b47c18
Tests: 1559 passed
Assertions: 14194
Push: origin/main confirmed
```

## Current known local situation

The latest confirmed successful phase is still Phase 77A.

Several Phase 77B attempts failed before commit and push. A clean rebuild
attempt restored `main` to `0b47c18`, then produced a PHP parse error in a
historical Phase 69B test. Therefore, the local working tree may currently be
dirty even though HEAD remains `0b47c18`.

Confirmed backup branch created during the clean-rebuild attempt:

```text
Branch: backup/phase77b-broken-20260716_121840
Commit: 9d67fea
Purpose: preserve earlier failed Phase 77B attempts
```

This branch may not contain the newest dirty state. Codex must create another
backup branch before resetting or cleaning the current working tree.

## Next phase

```text
Phase 77B — Implement Saved View CSV Export Writer
```

## Phase 77B required class

```text
App\Support\Reports\ReportSavedViewCsvExportWriter
```

Public API:

```php
public function write(iterable $formattedSavedViews): void
```

## Decisions to preserve

- The writer is final and stateless.
- The writer has no constructor dependencies.
- The writer owns CSV stream bytes only.
- The controller retains validation, query, user scope, formatting, filename,
  response, and content type.
- CSV header and format version come from
  `ReportSavedViewImportExportVersionRegistry`.
- Input order is preserved.
- The human summary and machine payload remain separate.
- Machine payload uses original filter keys and values.
- JSON uses `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`.
- Empty or failed payload encoding produces `{}`.
- Historical contract documents remain historical.
- Historical source-marker tests must be changed narrowly and intentionally.
- Do not run previous generated Phase 77B shell scripts.
- Do not begin Phase 77C until Phase 77B passes all focused and full tests,
  is documented, committed, pushed, and verified clean.

## First commands for a new Codex session

```bash
cd /c/laragon/www/talla-lin-erp
git status --short
git branch --show-current
git remote -v
git log -5 --oneline --decorate
git diff --stat
git ls-files --others --exclude-standard
git fetch origin
git rev-parse --short HEAD
git rev-parse --short origin/main
```
