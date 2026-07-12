# Phase 57B — Report Saved View Diagnostics Artisan Command

## Status

Phase 57B adds an Artisan command for report saved view registry diagnostics.

## Command

php artisan reports:saved-view-diagnostics

## JSON output

php artisan reports:saved-view-diagnostics --json

## Purpose

The command exposes the report saved view registry diagnostic report through the CLI.

It can be used for:

- local development checks
- deployment verification
- support diagnostics
- future CI hooks
- future admin tooling

## Source

The command is registered in:

routes/console.php

It uses:

app/Support/Reports/ReportSavedViewRegistryDiagnosticReport.php

## Expected healthy output

The current registry should show:

- report count: 1
- invalid count: 0
- valid: yes
- valid report key: sales-invoice-aging

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticsArtisanCommandTest


## Phase 57C command finalization

The JSON command output now uses ReportSavedViewRegistryDiagnosticReport::json.

This keeps command output logic centralized in the diagnostic report class.
