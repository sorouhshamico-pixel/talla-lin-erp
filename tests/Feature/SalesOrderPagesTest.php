<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesOrderPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_orders_index_page_can_be_viewed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sales-orders');

        $response->assertOk();
        $response->assertSee('أوامر البيع');
    }

    public function test_sales_order_show_page_can_be_viewed(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل صفحة أمر البيع',
            'phone' => '0502020202',
            'email' => 'sales-order-show@example.com',
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
            'notes' => 'عرض سعر مرتبط بأمر البيع',
        ]);

        $salesOrder = SalesOrder::create([
            'sales_order_number' => 'SO-000001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => 'draft',
            'total_amount' => 2500,
            'notes' => 'اختبار صفحة تفاصيل أمر البيع',
        ]);

        $salesOrder->items()->create([
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $response = $this->actingAs($user)->get('/sales-orders/' . $salesOrder->id);

        $response->assertOk();
        $response->assertSee('SO-000001');
        $response->assertSee('عميل صفحة أمر البيع');
        $response->assertSee('خرسانة جاهزة C30');
        $response->assertSee('2,500');
        $response->assertSee('draft');
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
