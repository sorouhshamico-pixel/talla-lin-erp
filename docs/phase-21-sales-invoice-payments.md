# Phase 21 — Sales Invoice Payments

## Overview

Phase 21 documents and strengthens the sales invoice payment workflow.

The module allows users to record payments against issued sales invoices, supports partial and full payments, updates invoice balances, and prevents invalid payment operations.

## Core Workflow

A payment can be recorded only for an issued sales invoice that still has a remaining amount.

When a payment is recorded, the system creates a row in:

sales_invoice_payments

Then it updates the related sales invoice balances.

## Payment Table

Table:

sales_invoice_payments

Main fields:

- sales_invoice_id
- user_id
- amount
- method
- reference_number
- notes
- paid_at

Supported payment methods:

- cash
- card
- bank_transfer
- online
- other

## Sales Invoice Balance Updates

When a valid payment is recorded, the invoice is updated as follows:

- paid_amount increases by the payment amount.
- remaining_amount decreases according to the paid amount.
- payment_status is recalculated.

## Payment Status Rules

The invoice payment status is:

- unpaid: no payment has been recorded.
- partial: some amount has been paid and remaining_amount is still greater than zero.
- paid: remaining_amount is zero.

## Guards

The system prevents:

- Recording a payment on a non-issued invoice.
- Recording a payment with amount less than or equal to zero.
- Recording a payment greater than the remaining amount.
- Opening the payment page for a draft invoice.
- Opening the payment page for a fully paid invoice.

## User Interface

### Sales Invoice Show Page

The invoice show page displays:

- Invoice total.
- Payment status.
- Paid amount.
- Remaining amount.
- Payment history.
- Payment notes.
- Payment errors.
- Register payment button only when the invoice is issued and still has a remaining amount.

### Payment Form

The payment form displays:

- Grand total.
- Paid amount.
- Remaining amount.
- Amount input.
- Payment method.
- Reference number.
- Notes.

The amount input uses the remaining amount as the maximum payment limit.

## Payment History

Payment history is displayed on the sales invoice show page.

The latest payment appears first.

Displayed columns:

- Date.
- Amount.
- Payment method.
- Reference number.
- User.
- Notes.

## Files Involved

Controllers:

- app/Http/Controllers/SalesInvoiceController.php

Models:

- app/Models/SalesInvoice.php
- app/Models/SalesInvoicePayment.php

Services:

- app/Services/SalesInvoiceService.php

Views:

- resources/views/sales-invoices/show.blade.php
- resources/views/sales-invoices/create-payment.blade.php

Migrations:

- database/migrations/2026_06_24_120400_create_sales_invoice_payments_table.php

Tests:

- tests/Feature/SalesInvoicePaymentTest.php
- tests/Feature/SalesInvoicePaymentAccessGuardTest.php
- tests/Feature/SalesInvoicePaymentErrorDisplayTest.php
- tests/Feature/SalesInvoicePaymentNotesDisplayTest.php
- tests/Feature/SalesInvoicePaymentFormLimitTest.php
- tests/Feature/SalesInvoicePaymentHistoryOrderTest.php

## Phase 21 Sub-Phases

### Phase 21A — Sales Invoice Payment Access Guards

Added access guards to prevent opening the payment page when the invoice is not eligible for payment.

### Phase 21B — Sales Invoice Payment Error Display

Added error display to the sales invoice show page.

### Phase 21C — Sales Invoice Payment Notes Display

Added payment notes to the payment history table.

### Phase 21D — Sales Invoice Payment Form Limits

Added a max limit and helper text to the payment amount field.

### Phase 21E — Sales Invoice Payment History Ordering

Ordered payment history so the latest payment appears first.

## Final Validation

Latest confirmed full test result after Phase 21E:

php artisan test = 473 passed / 3370 assertions

## Business Value

Phase 21 improves financial control over sales invoices.

It allows the ERP to track:

- Collected amounts.
- Outstanding balances.
- Partial collections.
- Full collections.
- Payment methods.
- Payment references.
- Payment notes.
- Payment history by invoice.
