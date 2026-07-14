# Phase 65D — Cash Flow Dashboard Print Saved View Eligibility Contract

## Status

Phase 65D does not roll out saved view controls.

The locked target is a print-only rendering surface and is not eligible for an independent saved view controls rollout.

## Baseline

- Phase: Phase 65C clean
- Commit: 5adb9e7
- Previous tests: 1145 passed / 9990 assertions

## Locked target

- Key: cash-flow-dashboard-print
- View path: resources/views/reports/cash-flow-dashboard-print.blade.php
- Priority score: 40
- Registered at lock time: no
- Has GET form at lock time: no
- Has filter terms at lock time: yes
- Has saved view controls at lock time: no

## Eligibility decision

- Status: not eligible for saved view controls rollout
- Reason: the target is a standalone print rendering surface.
- Saved view state should be owned by the parent cash-flow dashboard, not by the print page.
- Do not register this key in ReportSavedViewRegistry.
- Do not create a saved view config partial for this key.
- Do not add a saved view store route for this key.
- Do not modify the print view.

## Evidence

- Standalone print document: yes
- Contains interactive form: no
- Contains GET form: no
- Contains print button: yes
- Contains filter context display: yes
- Contains saved view include or inline config: no

## Data test ids found

- cash-flow-print-bucket-comparison
- cash-flow-print-button
- cash-flow-print-filter-context
- cash-flow-print-inflow-summary
- cash-flow-print-net-summary
- cash-flow-print-outflow-summary
- cash-flow-print-risk-summary

## Proposed exclusion

- Exclude candidates whose key ends with `-print`.
- Exclude candidates whose view path ends with `-print.blade.php`.
- Scope the exclusion to rollout selector/prioritization.

## Acceptance criteria

- The eligibility contract JSON exists.
- The eligibility contract markdown exists.
- The target matches the Phase 65C lock.
- The target view exists.
- The target is documented as not eligible.
- No implementation files are changed.
- Full php artisan test passes.

## Next step

Phase 65E should implement the selector exclusion for print-only views, then lock the next eligible interactive target.
