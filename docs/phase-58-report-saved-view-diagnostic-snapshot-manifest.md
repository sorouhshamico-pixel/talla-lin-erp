# Phase 58B — Report Saved View Diagnostic Snapshot Manifest

## Status

Phase 58B adds manifest tracking for report saved view diagnostic snapshot exports.

## Purpose

Every diagnostic snapshot export now updates a manifest file.

The manifest provides a stable machine-readable index of recent exports.

## Manifest file

The manifest is written to:

storage/app/report-saved-view-diagnostics/manifest.json

## Manifest content

The manifest includes:

- directory
- updated_at
- latest markdown snapshot
- latest JSON snapshot
- recent export history

## Export metadata

Each history entry includes:

- format
- filename
- relative_path
- exported_at
- healthy
- report_count
- invalid_count

## Snapshot exporter methods

ReportSavedViewDiagnosticSnapshotExporter now exposes:

- manifest
- manifestPath
- manifestRelativePath

## Why this matters

The diagnostic export feature is now ready for future admin pages, deployment checks, support handoff, or automated health exports.

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticSnapshotManifestTest
