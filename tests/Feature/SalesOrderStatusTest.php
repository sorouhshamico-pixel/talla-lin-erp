<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SalesOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_sales_order_status(): void
    {
        $user = User::factory()->create();

        $salesOrder = $this->createSalesOrder('draft');

        $response = $this->actingAs($user)->patch('/sales-orders/' . $salesOrder->id . '/status', [
            'status' => 'confirmed',
        ]);

        $response->assertRedirect('/sales-orders/' . $salesOrder->id);

        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_invalid_sales_order_status_is_rejected(): void
    {
        $user = User::factory()->create();

        $salesOrder = $this->createSalesOrder('draft');

        $response = $this->actingAs($user)
            ->from('/sales-orders/' . $salesOrder->id)
            ->patch('/sales-orders/' . $salesOrder->id . '/status', [
                'status' => 'wrong-status',
            ]);

        $response->assertRedirect('/sales-orders/' . $salesOrder->id);
        $response->assertSessionHasErrors('status');

        $this->assertDatabaseHas('sales_orders', [
            'id' => $salesOrder->id,
            'status' => 'draft',
        ]);
    }

    private function createSalesOrder(string $status): SalesOrder
    {
        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل حالة أمر البيع',
            'phone' => '0503030303',
            'email' => 'sales-order-status@example.com',
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
            'notes' => 'عرض سعر مرتبط بحالة أمر البيع',
        ]);

        return SalesOrder::create([
            'sales_order_number' => 'SO-000001',
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'sales_order_date' => now()->toDateString(),
            'status' => $status,
            'total_amount' => 2500,
            'notes' => 'اختبار حالة أمر البيع',
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
