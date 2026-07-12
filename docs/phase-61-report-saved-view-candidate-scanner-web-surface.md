# Phase 61B — Report Saved View Candidate Scanner Web Surface

## Status

Phase 61B adds an authenticated web surface for the report saved view candidate scanner.

## Routes

Candidate scanner page:

reports.saved-view-candidates.index

Markdown export:

reports.saved-view-candidates.markdown

JSON export:

reports.saved-view-candidates.json

## Middleware

All candidate scanner web routes use auth middleware.

## View

The candidate scanner view is:

resources/views/reports/saved-view-candidates.blade.php

## Page sections

The page displays:

- candidate count
- registered count
- unregistered count
- export actions
- candidate rows
- markdown snapshot

## Data source

The page uses:

app/Support/Reports/ReportSavedViewCandidateScanner.php

## Guard test

This phase is protected by:

ReportSavedViewCandidateScannerWebSurfaceTest

## Next step

Phase 61C can finalize candidate scanner discoverability and use the scanner output to select the next report rollout target.
