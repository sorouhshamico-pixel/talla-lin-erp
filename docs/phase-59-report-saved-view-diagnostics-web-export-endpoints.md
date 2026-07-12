# Phase 59B — Report Saved View Diagnostics Web Export Endpoints

## Status

Phase 59B adds web export endpoints for report saved view diagnostics.

## Routes

Markdown endpoint:

/reports/saved-view-diagnostics/markdown

Route name:

reports.saved-view-diagnostics.markdown

JSON endpoint:

/reports/saved-view-diagnostics/json

Route name:

reports.saved-view-diagnostics.json

## Middleware

Both endpoints use auth middleware.

## View integration

The diagnostics page now includes export action links for:

- View Markdown
- View JSON

## Data source

The endpoints use:

app/Support/Reports/ReportSavedViewRegistryDiagnosticReport.php

## Expected healthy output

Markdown output should include:

- Report Saved View Registry Diagnostic Report
- Report count: 1
- sales-invoice-aging

JSON output should include:

- title
- summary
- rows
- valid_report_keys
- invalid_reports

## Guard test

This phase is protected by:

ReportSavedViewDiagnosticsWebExportEndpointsTest
