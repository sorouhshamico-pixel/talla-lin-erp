# Phase 55B — Report Saved View Registry

## Status

Phase 55B adds a central registry for reports that support saved views.

## Purpose

The registry provides one stable place to identify:

- report key
- report label
- report view
- index route
- export route
- saved view store route
- saved view controls config partial
- hidden fields persisted by saved views
- important test IDs

## Registry class

The registry lives at:

app/Support/Reports/ReportSavedViewRegistry.php

## Initial registered report

The initial registered report is:

sales-invoice-aging

It references:

- resources/views/reports/sales-invoice-aging.blade.php
- resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php
- reports.sales-invoice-aging.index
- reports.sales-invoice-aging.export
- reports.sales-invoice-aging.saved-views.store

## Hidden fields

The sales invoice aging saved view stores:

- customer_id
- payment_status
- aging_bucket

## Why this matters

Future report saved view work should not rely on scattered string references.

The registry allows tests, documentation, and future UI surfaces to discover saved view capable reports consistently.

## Guard test

This phase is protected by:

ReportSavedViewRegistryTest
