# Phase 56A — Report Saved View Registry Metadata Helpers

## Status

Phase 56A adds metadata helper methods to ReportSavedViewRegistry.

## Purpose

The registry is now more than a static report list.

It can provide structured metadata for future UI, documentation, audits, and tests.

## Added helpers

ReportSavedViewRegistry now exposes:

- count
- labels
- viewPaths
- configPartials
- configPartialPaths
- indexRoutes
- exportRoutes
- savedViewStoreRoutes
- hiddenFieldMap
- testIdMap
- configPartialPath
- indexRoute
- savedViewStoreRoute
- documentationRows

## Why this matters

Future features can use registry helper methods instead of duplicating strings.

The metadata helpers make it easier to build:

- saved-view-capable report indexes
- admin diagnostics
- documentation generators
- consistency tests
- report capability summaries

## Reference report

The current reference report remains:

sales-invoice-aging

## Guard test

This phase is protected by:

ReportSavedViewRegistryMetadataHelpersTest


## Diagnostics integration

Phase 56C builds on the metadata helpers by adding diagnostics to ReportSavedViewRegistryValidator.

Diagnostics use registry metadata to produce valid report keys, invalid report rows, and report-level error summaries.
