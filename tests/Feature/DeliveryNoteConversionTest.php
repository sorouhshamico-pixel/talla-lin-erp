<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DeliveryNoteConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirmed_sales_order_can_be_converted_to_delivery_note(): void
    {
        $user = User::factory()->create();

        $salesOrder = $this->createSalesOrder('confirmed');

        $salesOrder->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response = $this->actingAs($user)->post('/sales-orders/' . $salesOrder->id . '/convert-to-delivery-note');

        $deliveryNote = DB::table('delivery_notes')->first();

        $this->assertNotNull($deliveryNote);
        $this->assertSame('DN-000001', $deliveryNote->delivery_note_number);
        $this->assertSame($salesOrder->id, $deliveryNote->sales_order_id);
        $this->assertSame($salesOrder->customer_id, $deliveryNote->customer_id);
        $this->assertSame('draft', $deliveryNote->status);
        $this->assertEquals(2500, (float) $deliveryNote->total_amount);

        $this->assertDatabaseHas('delivery_note_items', [
            'delivery_note_id' => $deliveryNote->id,
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response->assertRedirect('/delivery-notes/' . $deliveryNote->id);
    }

    public function test_non_confirmed_sales_order_cannot_be_converted_to_delivery_note(): void
    {
        $user = User::factory()->create();

        $salesOrder = $this->createSalesOrder('draft');

        $response = $this->actingAs($user)
            ->from('/sales-orders/' . $salesOrder->id)
            ->post('/sales-orders/' . $salesOrder->id . '/convert-to-delivery-note');

        $response->assertRedirect('/sales-orders/' . $salesOrder->id);
        $response->assertSessionHasErrors('sales_order_status');

        $this->assertDatabaseCount('delivery_notes', 0);
    }

    private function createSalesOrder(string $status): SalesOrder
    {
        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل سند التسليم',
            'phone' => '0505050505',
            'email' => 'delivery-note@example.com',
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
            'notes' => 'عرض سعر مرتبط بسند التسليم',
        ]);

        return SalesOrder::create([
            'sales_order_number' => 'SO-000001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => 2500,
            'notes' => 'أمر بيع مرتبط بسند التسليم',
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
