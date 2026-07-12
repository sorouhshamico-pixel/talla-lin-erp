# Phase 60A — Report Saved View Diagnostics Navigation Entry

## Status

Phase 60A improves discoverability for the report saved view diagnostics surface.

## Purpose

The diagnostics page is already available through a protected route.

This phase documents and guards the route so future report navigation work can expose it consistently.

## Route

Diagnostics page:

reports.saved-view-diagnostics.index

Markdown export:

reports.saved-view-diagnostics.markdown

JSON export:

reports.saved-view-diagnostics.json

## Navigation candidate files checked

- resources/views/reports/index.blade.php
- resources/views/reports/center.blade.php
- resources/views/reports/dashboard.blade.php

## Navigation files updated

- resources\views\reports\index.blade.php

## Fallback

If the project does not currently use a single report center Blade file, this phase keeps the diagnostics route discoverable through documentation and guard tests.

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticsNavigationEntryTest
