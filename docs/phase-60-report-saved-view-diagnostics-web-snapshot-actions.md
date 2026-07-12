# Phase 60B — Report Saved View Diagnostics Web Snapshot Actions

## Status

Phase 60B adds authenticated web snapshot actions to the report saved view diagnostics page.

## Routes

Write Markdown snapshot:

reports.saved-view-diagnostics.snapshots.markdown

Write JSON snapshot:

reports.saved-view-diagnostics.snapshots.json

Prune snapshots:

reports.saved-view-diagnostics.snapshots.prune

## Middleware

All snapshot action routes use auth middleware.

## View integration

The diagnostics page now displays Snapshot Actions with forms for:

- Write Markdown Snapshot
- Write JSON Snapshot
- Prune Snapshots
- Prune Snapshots And Manifest

## Exporter

The routes use:

app/Support/Reports/ReportSavedViewDiagnosticSnapshotExporter.php

## Storage

Generated snapshots are written under:

storage/app/report-saved-view-diagnostics

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticsWebSnapshotActionsTest


## Phase 60C snapshot action metadata

Phase 60C adds snapshotActionRoutes and snapshotActionItems to ReportSavedViewDiagnosticsWebLinks.

The diagnostics page displays route metadata for snapshot write and prune actions.
