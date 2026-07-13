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

## Phase 62B web links and navigation

Phase 62B adds ReportSavedViewRolloutSelectorWebLinks and exposes route metadata plus CLI command examples on the rollout selector page.

The candidate scanner page also links back to the rollout selector when the view exists.


## Phase 62C rollout selector finalization

Phase 62C finalizes the rollout selector surface.

The rollout selector now displays:

- web route metadata
- CLI command examples
- rollout workflow steps
- next candidate recommendation
- prioritized candidate table
