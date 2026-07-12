# Phase 56B — Report Saved View Registry Validator

## Status

Phase 56B adds a validator for the report saved view registry.

## Purpose

The validator gives the project a central way to check whether saved-view-capable reports are correctly registered.

It validates:

- required registry keys
- report view file existence
- config partial file existence
- index route existence
- export route existence
- saved view store route existence
- no direct saved-view-controls rendering inside report pages
- no inline SavedViewControlsConfig arrays inside report pages
- config partial renders saved-view-controls in the same scope
- config partial contains savedViews, section, form, and hiddenFields
- hidden fields appear in the config partial
- test IDs appear in the config partial

## Class

The validator lives at:

app/Support/Reports/ReportSavedViewRegistryValidator.php

## Main methods

- validate
- errorsFor
- isValid
- assertValid
- summary

## Why this matters

Future report work can add registry entries confidently.

The validator turns saved view registry drift into test failures instead of runtime surprises.

## Guard test

This phase is protected by:

ReportSavedViewRegistryValidatorTest
