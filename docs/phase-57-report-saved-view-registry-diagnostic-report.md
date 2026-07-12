# Phase 57A — Report Saved View Registry Diagnostic Report

## Status

Phase 57A adds a diagnostic report builder for the report saved view registry.

## Purpose

The registry validator can already validate reports and expose diagnostics.

This phase adds a higher-level report class that packages registry health into a reusable diagnostic report.

## Class

The report class lives at:

app/Support/Reports/ReportSavedViewRegistryDiagnosticReport.php

## Main methods

- build
- rows
- summary
- isHealthy
- validReportKeys
- invalidReports
- markdown

## Output

The diagnostic report includes:

- title
- validator summary
- diagnostic rows
- valid report keys
- invalid reports
- source classes

## Markdown report

The markdown method produces a readable diagnostic report that can later be used in:

- admin pages
- developer diagnostics
- support exports
- internal reports
- future Artisan commands

## Current expected health

The current registry should be healthy.

The valid report keys should include:

- sales-invoice-aging

The invalid report list should be empty.

## Guard test

This phase is protected by:

ReportSavedViewRegistryDiagnosticReportTest
