# Phase 63C — Locked Rollout Target Support Surface

## Status

Phase 63C adds a support surface for reading the locked rollout target and its inspection snapshot.

## Production files

- app/Support/Reports/ReportSavedViewRolloutTarget.php
- resources/views/reports/saved-view-rollout-target.blade.php
- routes/console.php
- routes/web.php

## CLI command

Markdown output:

php artisan reports:saved-view-rollout-target

JSON output:

php artisan reports:saved-view-rollout-target --json

## Web routes

Locked target page:

reports.saved-view-rollout-target.index

Markdown export:

reports.saved-view-rollout-target.markdown

JSON export:

reports.saved-view-rollout-target.json

## Data sources

- docs/phase-63-report-saved-view-rollout-target.json
- docs/phase-63-report-saved-view-rollout-target-inspection.json

## Guard test

This phase is protected by:

ReportSavedViewRolloutTargetSupportSurfaceTest

## Next step

Phase 63D can use this support class to implement saved view controls for the locked report target.
