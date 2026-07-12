# Phase 58A — Report Saved View Diagnostic Snapshot Export

## Status

Phase 58A adds snapshot export support for report saved view diagnostics.

## Purpose

The diagnostic report can now be written to disk for operational review.

## Snapshot exporter

The exporter lives at:

app/Support/Reports/ReportSavedViewDiagnosticSnapshotExporter.php

## Storage directory

Snapshots are written under:

storage/app/report-saved-view-diagnostics

## Supported formats

- markdown
- json

## Artisan command usage

Show diagnostics:

php artisan reports:saved-view-diagnostics

Show diagnostics as JSON:

php artisan reports:saved-view-diagnostics --json

Write markdown snapshot:

php artisan reports:saved-view-diagnostics --write

Write JSON snapshot:

php artisan reports:saved-view-diagnostics --write --format=json

Write JSON snapshot using the JSON shortcut:

php artisan reports:saved-view-diagnostics --write --json

## Why this matters

The project now has a reusable diagnostics export surface for:

- development checks
- support handoff
- deployment verification
- internal audit snapshots
- future dashboard features

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticSnapshotExportTest


## Phase 58B manifest integration

Phase 58B adds a manifest file for diagnostic snapshot exports.

The manifest is written to:

storage/app/report-saved-view-diagnostics/manifest.json

It records the latest markdown and JSON snapshots, health summary values, and recent export history.


## Phase 58C pruning integration

Phase 58C adds pruning support for diagnostic snapshot files.

The Artisan command now supports:

php artisan reports:saved-view-diagnostics --prune

The command preserves manifest.json by default.

To delete manifest.json as well, use:

php artisan reports:saved-view-diagnostics --prune --include-manifest
