<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Quotation;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryNoteToSalesInvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivered_delivery_note_can_be_converted_to_sales_invoice(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $deliveryNote->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response = $this->actingAs($user)->post('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice');

        $invoice = SalesInvoice::query()->first();

        $this->assertNotNull($invoice);
        $this->assertSame('INV-000001', $invoice->invoice_number);
        $this->assertSame($deliveryNote->customer_id, $invoice->customer_id);
        $this->assertSame('draft', $invoice->status);
        $this->assertSame('unpaid', $invoice->payment_status);
        $this->assertEquals(2500, (float) $invoice->subtotal);
        $this->assertEquals(0, (float) $invoice->tax_total);
        $this->assertEquals(2500, (float) $invoice->grand_total);
        $this->assertEquals(2500, (float) $invoice->remaining_amount);

        $this->assertDatabaseHas('sales_invoice_items', [
            'sales_invoice_id' => $invoice->id,
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_subtotal' => 2500,
            'line_total' => 2500,
        ]);

        $response->assertRedirect('/sales-invoices/' . $invoice->id);
    }

    public function test_non_delivered_delivery_note_cannot_be_converted_to_sales_invoice(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('draft');

        $response = $this->actingAs($user)
            ->from('/delivery-notes/' . $deliveryNote->id)
            ->post('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice');

        $response->assertRedirect('/delivery-notes/' . $deliveryNote->id);
        $response->assertSessionHasErrors('delivery_note_status');

        $this->assertDatabaseCount('sales_invoices', 0);
    }

    private function createDeliveryNote(string $status): DeliveryNote
    {
        $companyId = $this->createCompanyId();

        Branch::create([
            'company_id' => $companyId,
            'name' => 'فرع اختبار الفاتورة',
            'code' => 'INV-BRANCH',
            'type' => 'main',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'is_main' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => 'عميل فاتورة سند التسليم',
            'phone' => '0509090909',
            'email' => 'delivery-note-invoice@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'accepted',
            'total_amount' => 2500,
            'notes' => 'عرض سعر مرتبط بفاتورة سند التسليم',
        ]);

        $salesOrder = SalesOrder::create([
            'sales_order_number' => 'SO-000001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => 2500,
            'notes' => 'أمر بيع مرتبط بفاتورة سند التسليم',
        ]);

        return DeliveryNote::create([
            'delivery_note_number' => 'DN-000001',
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'delivery_note_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => 2500,
            'notes' => 'اختبار تحويل سند التسليم إلى فاتورة',
        ]);
    }

    private function createCompanyId(): int
    {
        $existingId = DB::table('companies')->value('id');

        if ($existingId) {
            return (int) $existingId;
        }

        $columns = DB::select('PRAGMA table_info(companies)');
        $data = [];

        foreach ($columns as $column) {
            $name = $column->name;
            $type = strtolower((string) $column->type);
            $isRequired = (int) $column->notnull === 1 && $column->dflt_value === null;

            if ($name === 'id') {
                continue;
            }

            if (in_array($name, ['created_at', 'updated_at'], true)) {
                $data[$name] = now();
                continue;
            }

            if (! $isRequired) {
                continue;
            }

            $data[$name] = match (true) {
                $name === 'name' || str_contains($name, 'company_name') => 'شركة اختبار',
                str_contains($name, 'email') => 'company@example.com',
                str_contains($name, 'phone') || str_contains($name, 'mobile') => '0500000000',
                str_contains($name, 'tax') => '300000000000003',
                str_contains($name, 'commercial') || str_contains($name, 'cr_number') => '1010000000',
                str_contains($name, 'user_id') => DB::table('users')->value('id') ?? User::factory()->create()->id,
                str_contains($name, 'is_') || str_contains($name, 'active') => true,
                str_contains($type, 'int') => 1,
                str_contains($type, 'real') || str_contains($type, 'float') || str_contains($type, 'double') || str_contains($type, 'decimal') => 0,
                str_contains($type, 'date') => now()->toDateString(),
                default => 'test-' . $name,
            };
        }

        return (int) DB::table('companies')->insertGetId($data);
    }
}
