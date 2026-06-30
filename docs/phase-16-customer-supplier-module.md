# Phase 16 — Customer and Supplier Module

## Final Status

Phase 16 closes the customer and supplier CRM layer.

The module now includes:

- Customer edit and update.
- Supplier edit and update.
- Customer and supplier detail pages.
- Active/inactive status toggle.
- CSV export and filtered CSV export.
- CSV templates.
- Print detail pages.
- Detail summary cards.
- CSV import.
- Bulk status update.
- Notes management.
- Attachments management.
- Contact logs.
- Follow-up center.
- Activity timeline.
- Follow-up completion and rescheduling.
- Financial summaries.
- Statement pages with date filters, print, and CSV export.
- Classifications/tags.
- Duplicate detection center.
- Access control.
- Customer and supplier dashboard cards.

## Main Routes

- customers.index
- customers.show
- customers.activity-timeline.index
- customers.statement
- suppliers.index
- suppliers.show
- suppliers.activity-timeline.index
- suppliers.statement
- party-follow-ups.index
- party-dashboard.index
- party-tags.index
- party-duplicates.index
- party-permissions.index

## Main Services

- PartyFinancialSummaryService
- PartyStatementService
- PartyDuplicateService
- PartyPermissionService
- PartyDashboardSummaryService

## Access Control

The party module uses `EnsurePartyPermission` middleware directly on protected routes.

Main permissions:

- view_parties
- manage_parties
- manage_party_notes
- manage_party_attachments
- manage_party_follow_ups
- manage_party_classifications
- export_parties
- view_party_financials

Roles:

- owner/admin: full access.
- manager: operational access.
- accountant: financial/export/follow-up access.
- viewer/user/staff: view-only access.

## Final Verification

```bash
php artisan test --filter=CustomerSupplierModuleFinalSmokeTest
php artisan test
