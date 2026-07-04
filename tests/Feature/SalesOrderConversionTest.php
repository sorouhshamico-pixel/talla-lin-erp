<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesOrderConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_quotation_can_be_converted_to_sales_order(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل أمر البيع',
            'phone' => '0599999999',
            'email' => 'sales-order@example.com',
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
            'notes' => 'تحويل عرض سعر مقبول إلى أمر بيع',
        ]);

        $quotation->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response = $this->actingAs($user)->post('/quotations/' . $quotation->id . '/convert-to-sales-order');

        $salesOrder = DB::table('sales_orders')->first();

        $this->assertNotNull($salesOrder);
        $this->assertSame('SO-000001', $salesOrder->sales_order_number);
        $this->assertSame($quotation->id, $salesOrder->quotation_id);
        $this->assertSame($customer->id, $salesOrder->customer_id);
        $this->assertSame('draft', $salesOrder->status);
        $this->assertEquals(2500, (float) $salesOrder->total_amount);

        $this->assertDatabaseHas('sales_order_items', [
            'sales_order_id' => $salesOrder->id,
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response->assertRedirect('/sales-orders/' . $salesOrder->id);
    }

    public function test_non_accepted_quotation_cannot_be_converted_to_sales_order(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل عرض غير مقبول',
            'phone' => '0501010101',
            'email' => 'non-accepted-quotation@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'sent',
            'total_amount' => 1000,
            'notes' => 'عرض سعر غير مقبول',
        ]);

        $response = $this->actingAs($user)
            ->from('/quotations/' . $quotation->id)
            ->post('/quotations/' . $quotation->id . '/convert-to-sales-order');

        $response->assertRedirect('/quotations/' . $quotation->id);
        $response->assertSessionHasErrors('quotation_status');

        $this->assertDatabaseCount('sales_orders', 0);
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
