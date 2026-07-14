# Phase 65M — Reports Center Eligibility Contract

## Status

Phase 65M does not roll out saved view controls.

The locked target is a reports navigation center and is not eligible for an independent saved view controls rollout.

## Baseline

- Phase: Phase 65L clean
- Commit: 2a0fd6e
- Previous tests: 1190 passed / 10434 assertions

## Locked target

- Key: center
- View path: resources/views/reports/center.blade.php
- Priority score: 10
- Registered at lock time: no
- Has GET form at lock time: no
- Has filter terms at lock time: no
- Has saved view controls at lock time: no
- Print-only candidate: no
- Internal tooling candidate at lock time: no

## Eligibility decision

- Status: not eligible for saved view controls rollout
- Reason: this is a reports navigation hub, not a business report surface.
- Do not register this key in ReportSavedViewRegistry.
- Do not create a saved view config partial for this key.
- Do not add a saved view store route for this key.
- Do not modify the navigation view.

## Evidence

- View exists: yes
- Standalone HTML document: yes
- Navigation hub: yes
- Contains reports center title: yes
- Contains financial dashboard card: yes
- Contains profit loss card: yes
- Contains profit loss export card: yes
- Contains interactive form: no
- Contains GET form: no
- Contains saved view include or inline config: no

## Linked routes

- reports.financial-dashboard
- reports.profit-loss
- reports.profit-loss.export

## Data test ids found

- reports-center
- reports-center-financial-dashboard-card
- reports-center-financial-dashboard-link
- reports-center-profit-loss-card
- reports-center-profit-loss-export-card
- reports-center-profit-loss-export-link
- reports-center-profit-loss-link

## Proposed exclusion

- Exclude report navigation hub candidates from rollout selection.
- Candidate key to exclude: `center`.
- Scope the exclusion to rollout selector/prioritization.

## Acceptance criteria

- The eligibility contract JSON exists.
- The eligibility contract markdown exists.
- The target matches the Phase 65L lock.
- The target view exists.
- The target is documented as not eligible.
- No implementation files are changed.
- Full php artisan test passes.

## Next step

Phase 65N should implement the selector exclusion for navigation hub views, then lock the next eligible business report target.
