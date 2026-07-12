# Report Saved View Controls Refactor

## Purpose

This document records the reusable Blade structure used for report saved view controls.

The refactor keeps report pages smaller by moving saved view listing, saved view form rendering, hidden filter fields, and report-specific configuration into focused partials.

## Main report integration

The sales invoice aging report loads saved view controls through this report-specific partial:

@include('reports.partials.sales-invoice-aging-saved-view-controls-config')

That report-specific config partial builds the configuration array and then renders:

@include('reports.partials.saved-view-controls', $salesInvoiceAgingSavedViewControlsConfig)

This avoids leaking config variables back into the parent view and keeps the render call in the same Blade scope as the config definition.

## Shared partials

### saved-view-controls.blade.php

Coordinates the saved view UI by loading:

- saved-view-section-card.blade.php
- saved-view-form-card.blade.php

It also applies default configuration for section and form options.

### saved-view-section-card.blade.php

Wraps the saved view section in a card and passes the report-specific test IDs and route name to the shared section partial.

### saved-view-section.blade.php

Renders the saved view content:

- saved view list styles
- help text
- active saved view banner
- saved view list
- manage saved views link

### saved-view-form-card.blade.php

Wraps the saved view creation form in a card and delegates hidden fields and visible form fields to smaller partials.

### saved-view-hidden-fields.blade.php

Renders hidden fields for the current report filters.

For the sales invoice aging report, these are:

- customer_id
- payment_status
- aging_bucket

### saved-view-form-fields.blade.php

Renders the visible saved view form fields:

- saved view name input
- default saved view checkbox
- save button

## Report-specific config partial

### sales-invoice-aging-saved-view-controls-config.blade.php

Defines the sales invoice aging report's saved view controls configuration.

It includes:

- saved view collection
- section card options
- section route and test IDs
- form card options
- save route
- form input test IDs
- hidden filter fields

## Partial inventory

The final saved view controls chain is:

1. sales-invoice-aging.blade.php
2. sales-invoice-aging-saved-view-controls-config.blade.php
3. saved-view-controls.blade.php
4. saved-view-section-card.blade.php
5. saved-view-section.blade.php
6. saved-view-list-styles.blade.php
7. saved-view-help-text.blade.php
8. active-saved-view-banner.blade.php
9. saved-view-list.blade.php
10. saved-view-form-card.blade.php
11. saved-view-hidden-fields.blade.php
12. saved-view-form-fields.blade.php

The inventory is protected by ReportSavedViewControlsPartialInventoryTest.

## Test coverage

The refactor is covered by:

- ReportSavedViewControlsPartialTest
- ReportSavedViewControlsConfigTest
- SalesInvoiceAgingSavedViewControlsConfigPartialTest
- ReportSavedViewControlsDefaultsTest
- ReportSavedViewSectionPartialTest
- ReportSavedViewSectionCardPartialTest
- ReportSavedViewFormFieldsPartialTest
- ReportSavedViewFormCardPartialTest
- ReportSavedViewHiddenFieldsPartialTest
- SalesInvoiceAgingSavedViewControlsRenderTest
- ReportSavedViewControlsPartialInventoryTest

## Safety rule

Do not define a config variable in a child partial and then use it in the parent view.

The config partial must render saved-view-controls inside the same partial where the config array is defined.
