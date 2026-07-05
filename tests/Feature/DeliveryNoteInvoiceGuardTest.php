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

class DeliveryNoteInvoiceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_delivered_delivery_note_cannot_be_converted_to_sales_invoice(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('draft');

        $deliveryNote->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response = $this->actingAs($user)
            ->from('/delivery-notes/' . $deliveryNote->id)
            ->post('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice');

        $response->assertRedirect('/delivery-notes/' . $deliveryNote->id);

        $this->assertDatabaseCount('sales_invoices', 0);
    }

    public function test_delivery_note_with_existing_sales_invoice_cannot_be_converted_again(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $deliveryNote->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $branch = Branch::query()
            ->where('company_id', $deliveryNote->customer->company_id)
            ->firstOrFail();

        $invoice = SalesInvoice::create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'customer_id' => $deliveryNote->customer_id,
            'delivery_note_id' => $deliveryNote->id,
            'user_id' => $user->id,
            'invoice_number' => 'INV-EXISTING-001',
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'currency' => 'SAR',
            'subtotal' => 2500,
            'discount_total' => 0,
            'tax_total' => 0,
            'grand_total' => 2500,
            'paid_amount' => 0,
            'remaining_amount' => 2500,
            'issued_at' => now(),
            'notes' => 'فاتورة موجودة مسبقًا',
        ]);

        $response = $this->actingAs($user)
            ->from('/delivery-notes/' . $deliveryNote->id)
            ->post('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice');

        $response->assertRedirect('/delivery-notes/' . $deliveryNote->id);

        $this->assertDatabaseCount('sales_invoices', 1);

        $this->assertDatabaseHas('sales_invoices', [
            'id' => $invoice->id,
            'delivery_note_id' => $deliveryNote->id,
            'invoice_number' => 'INV-EXISTING-001',
        ]);
    }

    public function test_delivered_delivery_note_without_items_cannot_be_converted_to_sales_invoice(): void
    {
        $user = User::factory()->create();

        $deliveryNote = $this->createDeliveryNote('delivered');

        $response = $this->actingAs($user)
            ->from('/delivery-notes/' . $deliveryNote->id)
            ->post('/delivery-notes/' . $deliveryNote->id . '/convert-to-sales-invoice');

        $response->assertRedirect('/delivery-notes/' . $deliveryNote->id);

        $this->assertDatabaseCount('sales_invoices', 0);
    }

    private function createDeliveryNote(string $status): DeliveryNote
    {
        $companyId = $this->createCompanyId();

        Branch::create([
            'company_id' => $companyId,
            'name' => 'فرع حمايات فاتورة سند التسليم',
            'code' => 'DN-INV-GUARD',
            'type' => 'main',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'is_main' => true,
            'is_active' => true,
        ]);

        $customer = Customer::create([
            'company_id' => $companyId,
            'name' => 'عميل حمايات فاتورة سند التسليم',
            'phone' => '0503334444',
            'email' => 'delivery-note-invoice-guard@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-GUARD-001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'accepted',
            'total_amount' => 2500,
            'notes' => 'عرض سعر لاختبار حمايات فاتورة سند التسليم',
        ]);

        $salesOrder = SalesOrder::create([
            'sales_order_number' => 'SO-GUARD-001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => 'confirmed',
            'total_amount' => 2500,
            'notes' => 'أمر بيع لاختبار حمايات فاتورة سند التسليم',
        ]);

        return DeliveryNote::create([
            'delivery_note_number' => 'DN-GUARD-001',
            'sales_order_id' => $salesOrder->id,
            'customer_id' => $customer->id,
            'delivery_note_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => 2500,
            'notes' => 'اختبار حمايات تحويل سند التسليم إلى فاتورة',
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
