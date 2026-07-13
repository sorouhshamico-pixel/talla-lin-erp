# Phase 62B — Rollout Selector Web Links And Navigation

## Status

Phase 62B adds centralized web links and navigation metadata for the rollout selector.

## Production files

- app/Support/Reports/ReportSavedViewRolloutSelectorWebLinks.php
- resources/views/reports/saved-view-rollout-selector.blade.php
- resources/views/reports/saved-view-candidates.blade.php
- routes/web.php

## Web links helper

The helper exposes route names for:

- rollout selector page
- rollout selector Markdown export
- rollout selector JSON export
- candidate scanner page
- diagnostics page

## CLI commands displayed

- php artisan reports:saved-view-rollout-selector
- php artisan reports:saved-view-rollout-selector --json
- php artisan reports:saved-view-candidates
- php artisan reports:saved-view-diagnostics

## Guard test

This phase is protected by:

ReportSavedViewRolloutSelectorWebLinksTest

## Next step

Phase 62C can finalize the rollout selector surface before using the recommendation for the next saved view rollout.
