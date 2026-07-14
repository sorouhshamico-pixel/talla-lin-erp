# Phase 65O — Financial Dashboard Saved View Controls Contract

## Status

Phase 65O prepares the rollout contract only. It does not modify implementation files.

## Baseline

- Phase: Phase 65N clean
- Commit: a21cd86
- Previous tests: 1200 passed / 10510 assertions

## Locked target

- Key: financial-dashboard
- View path: resources/views/reports/financial-dashboard.blade.php
- Controller: app/Http/Controllers/FinancialDashboardController.php
- Priority score: 10
- Registered at lock time: no
- Has GET form at lock time: no
- Has filter terms at lock time: no
- Has saved view controls at lock time: no
- Print-only candidate: no
- Internal tooling candidate: no
- Navigation hub candidate: no

## Eligibility decision

- Status: eligible for empty-filter saved view controls rollout
- Rollout mode: empty_filter_dashboard_saved_views
- Hidden fields: none
- Reason: this is a business financial dashboard with useful current-month metrics and no user filter form.

## Evidence

- View exists: yes
- Controller exists: yes
- Business dashboard: yes
- Controller is invokable: yes
- Controller uses schema guards: yes
- Contains current month revenues metric: yes
- Contains current month expenses metric: yes
- Contains current month net profit metric: yes
- Contains uncollected revenues metric: yes
- Contains unpaid expenses metric: yes
- Contains interactive form: no
- Contains GET form: no
- Contains saved view include or inline config: no

## Route contract

- Existing index route: reports.financial-dashboard
- JSON export route to add: reports.financial-dashboard.json
- JSON export path to add: /reports/financial-dashboard/json
- Saved view store route to add: reports.financial-dashboard.saved-views.store
- Saved view store path to add: /reports/financial-dashboard/saved-views

## Registry contract

- Key: financial-dashboard
- Label: الداشبورد المالية
- View: reports.financial-dashboard
- Index route: reports.financial-dashboard
- Export route: reports.financial-dashboard.json
- Store route: reports.financial-dashboard.saved-views.store
- Config partial path: resources/views/reports/partials/financial-dashboard-saved-view-controls-config.blade.php
- Hidden fields: []

## Data test ids found

- financial-dashboard
- financial-dashboard-current-month-expenses
- financial-dashboard-current-month-net-profit
- financial-dashboard-current-month-revenues
- financial-dashboard-profit-loss-export-link
- financial-dashboard-profit-loss-link
- financial-dashboard-profit-loss-link-card
- financial-dashboard-uncollected-revenues
- financial-dashboard-unpaid-expenses

## Acceptance criteria for Phase 65P rollout

- The financial dashboard renders saved view controls.
- Saving a financial dashboard saved view stores an empty filter payload.
- The registry includes financial-dashboard with empty hidden fields.
- The JSON export route exists and is route-backed.
- No shared saved-view partials are modified.
- Focused tests and full php artisan test pass.
