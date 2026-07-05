# Phase 20 — Delivery Note To Sales Invoice Conversion

## Overview

Phase 20 completes the workflow that converts a delivered delivery note into a linked sales invoice.

This phase connects the delivery cycle with the sales invoicing cycle and prevents duplicate invoice creation for the same delivery note.

## Completed Sub-Phases

### Phase 20A — Delivery Note To Sales Invoice Conversion

Added the main conversion workflow.

Route:

POST /delivery-notes/{deliveryNote}/convert-to-sales-invoice

Controller:

App\Http\Controllers\DeliveryNoteInvoiceController

Rules:

- Only delivery notes with status delivered can be converted.
- A delivery note cannot be converted twice.
- The generated sales invoice is linked using delivery_note_id.
- Sales invoice items are created from delivery note items.

### Phase 20B — Show Invoice Link On Delivery Note

Updated the delivery note show page.

UI behavior:

- Show تحويل إلى فاتورة مبيعات when the delivery note is delivered and has no invoice.
- Show the linked invoice when an invoice already exists.
- Hide the conversion button for non-delivered delivery notes.

Added relationship:

DeliveryNote::salesInvoice()

This relationship uses:

hasOne(SalesInvoice::class, 'delivery_note_id')

### Phase 20C — Delivery Note Invoice Status Display

Added linked invoice status details inside the delivery note page.

Displayed fields:

- Invoice number.
- Invoice status.
- Payment status.
- Grand total.
- Issue date.
- Link to open the invoice.

Uses SalesInvoice helpers:

- displayStatus()
- displayPaymentStatus()

### Phase 20D — Delivery Note Invoice Guard Tests

Added guard coverage for invalid conversion cases.

Protected cases:

- Non-delivered delivery notes cannot be converted.
- Delivery notes with an existing invoice cannot be converted again.
- Delivered delivery notes without items cannot be converted.

Empty items error key:

delivery_note_items

Error message:

لا يمكن تحويل سند التسليم إلى فاتورة مبيعات بدون بنود.

### Phase 20E — Delivery Note Conversion UI Polish

Improved the conversion user interface.

Added:

- Confirmation before conversion.
- Helper text beside the conversion button.
- Success message after conversion.

Success message:

تم تحويل سند التسليم إلى فاتورة مبيعات بنجاح.

## Files Involved

Controllers:

- app/Http/Controllers/DeliveryNoteInvoiceController.php
- app/Http/Controllers/DeliveryNoteController.php

Models:

- app/Models/DeliveryNote.php
- app/Models/SalesInvoice.php

Views:

- resources/views/delivery-notes/show.blade.php

Migration:

- database/migrations/2026_07_04_150000_add_delivery_note_id_to_sales_invoices_table.php

Tests:

- tests/Feature/DeliveryNoteToSalesInvoiceTest.php
- tests/Feature/DeliveryNoteInvoiceLinkTest.php
- tests/Feature/DeliveryNoteInvoiceStatusDisplayTest.php
- tests/Feature/DeliveryNoteInvoiceGuardTest.php
- tests/Feature/DeliveryNoteConversionUiPolishTest.php

## Final Validation

Latest confirmed full test result before this documentation phase:

php artisan test = 466 passed / 3342 assertions

## Business Value

Phase 20 completes the operational link between delivery notes and sales invoices.

The system can now:

- Convert delivered delivery notes into sales invoices.
- Prevent duplicate invoices for the same delivery note.
- Display the linked invoice directly from the delivery note.
- Show invoice and payment status on the delivery note page.
- Protect the conversion workflow with automated tests.
