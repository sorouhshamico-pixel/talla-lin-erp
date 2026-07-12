# Phase 59A — Report Saved View Diagnostics Web View

## Status

Phase 59A adds a web page for report saved view diagnostics.

## Route

The page is available at:

/reports/saved-view-diagnostics

Route name:

reports.saved-view-diagnostics.index

## Middleware

The route uses auth middleware.

## View

The Blade view lives at:

resources/views/reports/saved-view-diagnostics.blade.php

## Data source

The page uses:

app/Support/Reports/ReportSavedViewRegistryDiagnosticReport.php

## Page sections

The page displays:

- summary cards
- valid report keys
- diagnostic rows
- markdown snapshot

## Current expected values

The current page should show:

- report count: 1
- invalid count: 0
- healthy status
- sales-invoice-aging
- customer_id, payment_status, aging_bucket

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticsWebViewTest
