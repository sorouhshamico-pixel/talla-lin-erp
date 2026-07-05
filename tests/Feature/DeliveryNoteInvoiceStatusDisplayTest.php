<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DeliveryNoteInvoiceStatusDisplayTest extends TestCase
{
    public function test_delivery_note_show_view_displays_linked_sales_invoice_status_details(): void
    {
        $deliveryNote = new DeliveryNote();
        $deliveryNote->forceFill([
            'id' => 2001,
            'delivery_note_number' => 'DN-20C-001',
            'status' => 'delivered',
            'total_amount' => 1250.75,
            'delivery_note_date' => Carbon::parse('2026-07-05'),
            'notes' => null,
        ]);

        $customer = new Customer();
        $customer->forceFill([
            'id' => 3001,
            'name' => 'عميل تجريبي',
        ]);

        $salesOrder = new SalesOrder();
        $salesOrder->forceFill([
            'id' => 4001,
            'sales_order_number' => 'SO-20C-001',
        ]);

        $salesInvoice = new SalesInvoice();
        $salesInvoice->forceFill([
            'id' => 5001,
            'invoice_number' => 'INV-20C-001',
            'status' => 'issued',
            'payment_status' => 'partial',
            'grand_total' => 1250.75,
            'issued_at' => Carbon::parse('2026-07-05'),
        ]);

        $deliveryNote->setRelation('customer', $customer);
        $deliveryNote->setRelation('salesOrder', $salesOrder);
        $deliveryNote->setRelation('items', new Collection());
        $deliveryNote->setRelation('salesInvoice', $salesInvoice);

        $this->withViewErrors([])
            ->view('delivery-notes.show', [
                'deliveryNote' => $deliveryNote,
            ])
            ->assertSee('تفاصيل الفاتورة المرتبطة')
            ->assertSee('رقم الفاتورة')
            ->assertSee('INV-20C-001')
            ->assertSee('حالة الفاتورة')
            ->assertSee('معتمدة')
            ->assertSee('حالة السداد')
            ->assertSee('مدفوعة جزئيًا')
            ->assertSee('إجمالي الفاتورة')
            ->assertSee('1,250.75 ريال')
            ->assertSee('تاريخ الفاتورة')
            ->assertSee('2026-07-05')
            ->assertSee('فتح الفاتورة');
    }

    public function test_delivery_note_show_view_does_not_display_invoice_status_details_without_invoice(): void
    {
        $deliveryNote = new DeliveryNote();
        $deliveryNote->forceFill([
            'id' => 2002,
            'delivery_note_number' => 'DN-20C-002',
            'status' => 'draft',
            'total_amount' => 0,
            'delivery_note_date' => Carbon::parse('2026-07-05'),
            'notes' => null,
        ]);

        $customer = new Customer();
        $customer->forceFill([
            'id' => 3002,
            'name' => 'عميل تجريبي',
        ]);

        $salesOrder = new SalesOrder();
        $salesOrder->forceFill([
            'id' => 4002,
            'sales_order_number' => 'SO-20C-002',
        ]);

        $deliveryNote->setRelation('customer', $customer);
        $deliveryNote->setRelation('salesOrder', $salesOrder);
        $deliveryNote->setRelation('items', new Collection());
        $deliveryNote->setRelation('salesInvoice', null);

        $this->withViewErrors([])
            ->view('delivery-notes.show', [
                'deliveryNote' => $deliveryNote,
            ])
            ->assertDontSee('تفاصيل الفاتورة المرتبطة')
            ->assertDontSee('رقم الفاتورة')
            ->assertDontSee('حالة السداد')
            ->assertDontSee('فتح الفاتورة');
    }
}
