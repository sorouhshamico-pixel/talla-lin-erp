# Phase 23 — Supplier Account Statement

## Overview

Phase 23 improves and documents the supplier account statement workflow.

The supplier statement now has a clearer financial source, supports expenses linked directly to suppliers, displays supplier expense rows, maps payment status correctly, exports CSV totals, and respects date filters.

---

## Final Scope

Phase 23 includes:

- Showing source labels on the supplier statement page.
- Linking expenses to suppliers through `supplier_id`.
- Adding a supplier relationship to the `Expense` model.
- Displaying supplier-linked expense rows in the supplier statement.
- Mapping `expenses.is_paid` to `paid` / `unpaid`.
- Testing supplier statement CSV totals.
- Testing supplier statement date filters for page and CSV export.

---

## Phase 23A — Supplier Statement Expense Labels

### Purpose

Clarify the source and financial meaning of the supplier statement.

### Page Labels

The supplier statement page explains:

- The source is expenses linked to the supplier.
- Debit represents expense amounts due to the supplier.
- Credit represents paid or deducted amounts when available in the system.

### View

resources/views/party-statements/show.blade.php

### Test

tests/Feature/SupplierStatementExpenseLabelsTest.php

---

## Phase 23B — Supplier Statement Expense Rows

### Purpose

Enable the supplier statement to show actual expense rows linked to a supplier.

### Database

A nullable `supplier_id` column was added to the `expenses` table.

Migration:

database/migrations/2026_07_05_230000_add_supplier_id_to_expenses_table.php

### Model

The `Expense` model now includes:

- `supplier_id` as fillable.
- `supplier()` relationship.

Model:

app/Models/Expense.php

### Service

The statement service now maps `expenses.is_paid` to readable statement status:

- `true` => `paid`
- `false` => `unpaid`

Service:

app/Services/PartyStatementService.php

### Test

tests/Feature/SupplierStatementExpenseRowsTest.php

---

## Phase 23C — Supplier Statement CSV Expense Totals

### Purpose

Verify that supplier statement CSV export includes expense rows and totals.

### CSV Includes

- Header row.
- Supplier expense row.
- `total_debit`.
- `total_credit`.
- Final `balance`.

### Test

tests/Feature/SupplierStatementCsvTotalsTest.php

---

## Phase 23D — Supplier Statement Date Filters

### Purpose

Verify that supplier statement date filters work for both the page and CSV export.

### Tested Behavior

The supplier statement:

- Shows expenses inside the selected date range.
- Hides expenses outside the selected date range.
- Applies date filters on the page.
- Applies date filters on CSV export.

### Test

tests/Feature/SupplierStatementDateFilterTest.php

---

## Files Involved

Controllers:

- app/Http/Controllers/PartyStatementController.php

Services:

- app/Services/PartyStatementService.php

Models:

- app/Models/Expense.php
- app/Models/Supplier.php

Views:

- resources/views/party-statements/show.blade.php

Migrations:

- database/migrations/2026_07_05_230000_add_supplier_id_to_expenses_table.php

Tests:

- tests/Feature/SupplierStatementExpenseLabelsTest.php
- tests/Feature/SupplierStatementExpenseRowsTest.php
- tests/Feature/SupplierStatementCsvTotalsTest.php
- tests/Feature/SupplierStatementDateFilterTest.php

Documentation:

- docs/phase-23-supplier-account-statement.md

---

## Final Validation

Latest confirmed full test result after Phase 23D:

php artisan test = 484 passed / 3456 assertions

---

## Business Value

Phase 23 makes supplier account statements more useful for financial tracking.

It allows the ERP to track:

- Supplier-linked expenses.
- Amounts due to suppliers.
- Supplier payment status through expense payment state.
- Date-filtered supplier balances.
- CSV-exported supplier statement totals.
