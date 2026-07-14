# Phase 66A — Saved View Management UX Audit Contract

## Baseline

- Previous phase: Phase 65Q clean
- Commit: 308ab02
- Previous tests: 1216 passed / 10744 assertions

## Scope

This phase is an audit contract only. It must not change controllers, routes, views, models, migrations, or shared saved view partials.

## Current management surfaces

- Controller: app/Http/Controllers/ReportSavedViewController.php
- Index view: resources/views/reports/saved-views/index.blade.php
- Edit view: resources/views/reports/saved-views/edit.blade.php
- Model: app/Models/ReportSavedView.php
- Routes: routes/web.php

## Current capabilities

- Index/list saved views: yes
- Edit saved view: yes
- Update saved view: yes
- Duplicate saved view: yes
- Apply saved view: yes
- Make default: yes
- Delete one: yes
- Delete all: yes

## Registry alignment

- Registry report count: 13
- Controller static label key count: 7
- Controller static route key count: 7

### Missing label keys from controller static map

- financial-dashboard
- index
- profit-loss
- sales-invoice-collection-follow-ups
- sales-invoice-collections
- saved-view-candidates

### Missing route keys from controller static map

- financial-dashboard
- index
- profit-loss
- sales-invoice-collection-follow-ups
- sales-invoice-collections
- saved-view-candidates

## Audit findings

### registry_alignment_gap

- Severity: high
- Finding: ReportSavedViewController uses private static report label/route maps instead of ReportSavedViewRegistry.
- Impact: newly registered reports can have saved views but still display raw keys or fail management open/apply links until static maps are updated.

### index_action_density

- Severity: medium
- Finding: many actions are rendered inside one table cell.
- Impact: scanability and mobile usability can degrade.

### edit_filter_mutation_risk

- Severity: medium
- Finding: edit copy says filters are read-only, but filter inputs are editable and update accepts filters.
- Impact: users can modify filter payloads without report-specific validation.

### ownership_guard_present

- Severity: positive
- Finding: per-record operations call authorizeSavedView.
- Impact: saved view ownership is protected.

## Phase 66B recommendations

- Replace static report labels/routes in ReportSavedViewController with ReportSavedViewRegistry lookups.
- Add coverage proving every registered report key has a management label and apply/open route.
- Decide whether saved view filters are editable or read-only in the edit UX.
- Add an action menu/grouped row action layout.
- Keep ownership guards and add explicit cross-user access tests.
