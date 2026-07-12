# Report Saved View Controls Extension Guide

## Purpose

This guide explains how to add saved view controls to any report without copying saved view markup directly into the report page.

The accepted pattern is:

1. Keep the report page focused on report layout.
2. Add one report-specific saved view controls config partial.
3. Define the saved view controls config array inside that partial.
4. Render the shared saved-view-controls partial from the same config partial.

## Naming convention

For a report view named:

resources/views/reports/example-report.blade.php

Create this config partial:

resources/views/reports/partials/example-report-saved-view-controls-config.blade.php

## Report page usage

The report page should include only its report-specific config partial:

@include('reports.partials.example-report-saved-view-controls-config')

The report page should not include saved-view-controls directly.

## Config partial structure

The config partial should define one config array with these top-level keys:

- savedViews
- section
- form
- hiddenFields

The config partial should then render:

@include('reports.partials.saved-view-controls', $exampleReportSavedViewControlsConfig)

## Required config keys

### savedViews

Pass the report saved views collection.

### section

Include report-specific section options:

- cardTestId
- routeName
- emptyTestId
- listTestId
- itemTestId
- openLinkTestId
- activeBadgeTestId
- defaultBadgeTestId
- manageLinkTestId

### form

Include report-specific form options:

- cardTestId
- title
- storeRouteName
- testId
- nameInputId
- namePlaceholder
- nameInputTestId
- defaultCheckboxTestId
- saveButtonTestId

### hiddenFields

Include the report filters that should be persisted when saving the view.

For the sales invoice aging report, the hidden fields are:

- customer_id
- payment_status
- aging_bucket

## Safety rules

Do not inline saved view controls markup in report pages.

Do not define a saved view controls config array in a child partial and then use it in the parent view.

Do not call saved-view-controls directly from a report page.

Do define and render the config inside the same report-specific config partial.

## Existing reference implementation

Use this file as the reference implementation:

resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php

## Guard tests

This pattern is protected by:

- ReportSavedViewControlsConfigRolloutTest
- ReportSavedViewControlsExtensionGuideTest
- ReportSavedViewControlsFinalizationTest
- SalesInvoiceAgingSavedViewInlineMarkupGuardTest
