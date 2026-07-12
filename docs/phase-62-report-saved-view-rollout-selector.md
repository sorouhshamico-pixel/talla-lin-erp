# Phase 62A — Report Saved View Rollout Selector

## Status

Phase 62A adds a rollout selector for choosing the next report saved view rollout target.

## Production files

- app/Support/Reports/ReportSavedViewRolloutSelector.php
- resources/views/reports/saved-view-rollout-selector.blade.php
- routes/console.php
- routes/web.php

## CLI command

Markdown output:

php artisan reports:saved-view-rollout-selector

JSON output:

php artisan reports:saved-view-rollout-selector --json

## Web routes

Rollout selector page:

reports.saved-view-rollout-selector.index

Markdown export:

reports.saved-view-rollout-selector.markdown

JSON export:

reports.saved-view-rollout-selector.json

## Selection logic

The selector uses unregistered candidates from ReportSavedViewCandidateScanner and sorts by:

- highest priority score first
- key name as a deterministic tie breaker

## Guard test

This phase is protected by:

ReportSavedViewRolloutSelectorTest

## Next step

Phase 62B can use the selected next candidate to start a concrete saved view rollout for that report.
