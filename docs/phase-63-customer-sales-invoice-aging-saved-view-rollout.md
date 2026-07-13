# Phase 63D — Customer Sales Invoice Aging Saved View Rollout

## Status

Phase 63D rolls out shared saved view controls to the customer sales invoice aging report.

## Target

Key:

customer-sales-invoice-aging

View:

resources/views/reports/customer-sales-invoice-aging.blade.php

## Changes

This phase:

- extracts the inline customer aging saved view markup into a report-specific config partial
- registers customer-sales-invoice-aging in ReportSavedViewRegistry
- keeps the existing filter fields customer_id and aging_bucket
- preserves existing test ids for the saved view selector and save form
- updates the Phase 63A lock test so the locked target can move from unregistered to registered after rollout

## Production files

- resources/views/reports/customer-sales-invoice-aging.blade.php
- resources/views/reports/partials/customer-sales-invoice-aging-saved-view-controls-config.blade.php
- app/Support/Reports/ReportSavedViewRegistry.php

## Registry entry

The registry entry uses:

- index route: reports.customer-sales-invoice-aging.index
- export route: reports.customer-sales-invoice-aging.export
- saved view store route: reports.customer-sales-invoice-aging.saved-views.store
- config partial: reports.partials.customer-sales-invoice-aging-saved-view-controls-config

## Hidden fields

- customer_id
- aging_bucket

## Guard tests

This phase is protected by:

CustomerSalesInvoiceAgingSavedViewRolloutTest

## Next step

Phase 63E can finalize the customer sales invoice aging rollout with documentation and diagnostics confirmation.
