# Phase 61A — Report Saved View Candidate Scanner

## Status

Phase 61A adds a scanner for identifying report views that are candidates for saved view support.

## Purpose

After completing diagnostics across registry, CLI, snapshots, and web, the next step is expanding saved views to more reports.

The scanner provides a safe inventory of candidate report pages before changing report-specific Blade files.

## Production files

- app/Support/Reports/ReportSavedViewCandidateScanner.php
- routes/console.php

## Artisan command

Markdown output:

php artisan reports:saved-view-candidates

JSON output:

php artisan reports:saved-view-candidates --json

## Candidate signals

The scanner inspects top-level Blade report views in:

resources/views/reports

It excludes:

- partials
- saved-view-diagnostics.blade.php
- underscore-prefixed Blade files

It records:

- report key
- view path
- registry status
- GET form presence
- filter terms
- saved view controls presence
- priority score

## Next step

Phase 61B can use the scanner output to choose the next report for saved view rollout without guessing.

## Guard test

This phase is protected by:

ReportSavedViewCandidateScannerTest
