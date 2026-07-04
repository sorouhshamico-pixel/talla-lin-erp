<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuotationItemsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_item_to_quotation_and_see_total_on_show_page(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل بنود عرض السعر',
            'phone' => '0522222222',
            'email' => 'quotation-items@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'notes' => 'اختبار بنود عرض السعر',
        ]);

        $response = $this->actingAs($user)->post('/quotations/' . $quotation->id . '/items', [
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
        ]);

        $response->assertRedirect('/quotations/' . $quotation->id);

        $this->assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id,
            'description' => 'خرسانة جاهزة C30',
            'quantity' => 10,
            'unit_price' => 250,
            'line_total' => 2500,
        ]);

        $showResponse = $this->actingAs($user)->get('/quotations/' . $quotation->id);

        $showResponse->assertOk();
        $showResponse->assertSee('خرسانة جاهزة C30');
        $showResponse->assertSee('2,500');
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
