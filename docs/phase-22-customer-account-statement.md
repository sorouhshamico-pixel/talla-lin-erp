# Phase 22 — Customer Account Statement

## Overview

Phase 22 improves and documents the customer account statement workflow.

The customer statement now uses sales invoices and sales invoice payments as its financial source. This makes the statement aligned with the invoicing and collection workflow completed in Phase 21.

## Final Scope

Phase 22 includes:

- Adding a customer statement link to the customer show page.
- Building customer statement rows from sales invoices and sales invoice payments.
- Showing sales invoices as debit rows.
- Showing invoice payments as credit rows.
- Calculating a running balance.
- Adding labels that explain debit and credit meaning.
- Adding CSV totals.
- Testing invoice and payment rows on the customer statement page.

---

## Phase 22A — Customer Statement Link On Customer Page

### Purpose

Add direct access to the customer account statement from the customer details page.

### UI

The customer show page includes:

- كشف حساب العميل

### Route

customers.statement

### View

resources/views/customers/show.blade.php

### Test

tests/Feature/CustomerStatementLinkTest.php

---

## Phase 22B — Customer Statement Sales Invoices Source

### Purpose

Change the customer account statement source from generic revenues to the actual sales invoice and payment workflow.

### Source Tables

The customer statement now uses:

- sales_invoices
- sales_invoice_payments

### Statement Rules

Sales invoices are displayed as debit rows.

Sales invoice payments are displayed as credit rows.

The running balance is calculated as:

debit - credit

### Row Types

Invoice row:

- type: فاتورة بيع
- debit: invoice grand total
- credit: 0

Payment row:

- type: دفعة
- debit: 0
- credit: payment amount

### Service

app/Services/PartyStatementService.php

### Test

tests/Feature/CustomerStatementSalesInvoiceSourceTest.php

---

## Phase 22C — Customer Statement Page Invoice Labels

### Purpose

Clarify the meaning of the customer statement for users.

### Page Labels

The customer statement page explains:

- The source is sales invoices and sales invoice payments.
- Debit means sales invoice amounts due from the customer.
- Credit means payments received from the customer.

### View

resources/views/party-statements/show.blade.php

### Test

tests/Feature/CustomerStatementInvoiceLabelsTest.php

---

## Phase 22D — Customer Statement CSV Totals

### Purpose

Improve CSV exports by adding statement totals.

### CSV Summary Rows

The CSV export now includes:

- total_debit
- total_credit
- balance

### Controller

app/Http/Controllers/PartyStatementController.php

### Test

tests/Feature/CustomerStatementCsvTotalsTest.php

---

## Phase 22E — Customer Statement Page Invoice Payment Rows

### Purpose

Verify that the customer statement page displays invoice rows, payment rows, and running balance correctly.

### Tested Display

The page displays:

- Sales invoice row.
- Payment row.
- Debit amount.
- Credit amount.
- Final running balance.

### Test

tests/Feature/CustomerStatementInvoicePaymentRowsTest.php

---

## Files Involved

Controllers:

- app/Http/Controllers/PartyStatementController.php

Services:

- app/Services/PartyStatementService.php

Views:

- resources/views/customers/show.blade.php
- resources/views/party-statements/show.blade.php

Tests:

- tests/Feature/CustomerStatementLinkTest.php
- tests/Feature/CustomerStatementSalesInvoiceSourceTest.php
- tests/Feature/CustomerStatementInvoiceLabelsTest.php
- tests/Feature/CustomerStatementCsvTotalsTest.php
- tests/Feature/CustomerStatementInvoicePaymentRowsTest.php

Documentation:

- docs/phase-22-customer-account-statement.md

---

## Final Validation

Latest confirmed full test result after Phase 22E:

php artisan test = 479 passed / 3419 assertions

---

## Business Value

Phase 22 gives the ERP a practical customer account statement connected to the actual sales cycle.

It allows the business to track:

- Customer sales invoices.
- Customer payments.
- Amounts due from customers.
- Running balance.
- CSV-exported customer balances.
- Statement-level financial history.
