# Phase 65F — Sales Invoice Collections Saved View Controls Contract

## Baseline

- Phase: Phase 65E clean
- Commit: dd92db4
- Previous tests: 1155 passed / 10046 assertions

## Locked target

- Key: sales-invoice-collections
- View path: resources/views/reports/sales-invoice-collections.blade.php
- Controller path: app/Http/Controllers/SalesInvoiceCollectionReportController.php
- Priority score: 40
- Registered at lock time: no
- Print-only candidate: no

## Eligibility decision

- Status: eligible for empty-filter saved view controls rollout
- Reason: non-print production report surface with no GET filter form.
- Rollout mode: empty filter report saved views.
- Add a saved view controls config partial.
- Add a saved view store route.
- Add a JSON export route for registry export route support.
- Register the report in ReportSavedViewRegistry.

## Current state evidence

- View exists: yes
- Controller exists: yes
- Standalone HTML document: yes
- Contains interactive form: no
- Contains GET form: no
- Contains saved view include or inline config: no
- Existing index route: reports.sales-invoice-collections.index
- JSON route exists now: no
- Saved view store route exists now: no

## Data test ids found

- collection-invoice-row
- collection-invoices-empty
- collection-outstanding-count
- collection-outstanding-total
- collection-overdue-count
- collection-overdue-total
- collection-partial-count
- collection-partial-total
- collection-unpaid-count
- collection-unpaid-total
- sales-invoice-collection-invoices-card
- sales-invoice-collection-report-back-link
- sales-invoice-collection-report-overdue-link
- sales-invoice-collection-report-page
- sales-invoice-collection-summary-card

## Route contract

- Existing index route: reports.sales-invoice-collections.index
- JSON export route to add: reports.sales-invoice-collections.json
- Saved view store route to add: reports.sales-invoice-collections.saved-views.store

## View contract

- Config partial: reports.partials.sales-invoice-collections-saved-view-controls-config
- Config partial path: resources/views/reports/partials/sales-invoice-collections-saved-view-controls-config.blade.php
- Hidden fields: none
- Status alert test id: sales-invoice-collections-status

## Registry contract

- Key: sales-invoice-collections
- Label: تقرير تحصيل فواتير المبيعات
- Export route: reports.sales-invoice-collections.json
- Saved view store route: reports.sales-invoice-collections.saved-views.store

## Next step

Phase 65G should roll out the contract without modifying shared saved view partials.
