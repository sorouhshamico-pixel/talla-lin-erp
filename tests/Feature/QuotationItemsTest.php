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


    public function test_user_can_update_quotation_item_and_recalculate_line_total(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل تعديل بند عرض السعر',
            'phone' => '0533333333',
            'email' => 'quotation-item-update@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'notes' => 'اختبار تعديل بند',
        ]);

        $item = $quotation->items()->create([
            'description' => 'خرسانة جاهزة C25',
            'quantity' => 5,
            'unit_price' => 200,
            'line_total' => 1000,
        ]);

        $response = $this->actingAs($user)->patch('/quotations/' . $quotation->id . '/items/' . $item->id, [
            'description' => 'خرسانة جاهزة C35',
            'quantity' => 8,
            'unit_price' => 275,
        ]);

        $response->assertRedirect('/quotations/' . $quotation->id);

        $this->assertDatabaseHas('quotation_items', [
            'id' => $item->id,
            'description' => 'خرسانة جاهزة C35',
            'quantity' => 8,
            'unit_price' => 275,
            'line_total' => 2200,
        ]);

        $showResponse = $this->actingAs($user)->get('/quotations/' . $quotation->id);

        $showResponse->assertOk();
        $showResponse->assertSee('خرسانة جاهزة C35');
        $showResponse->assertSee('2,200');
    }


    public function test_user_can_delete_quotation_item(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل حذف بند عرض السعر',
            'phone' => '0544444444',
            'email' => 'quotation-item-delete@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'notes' => 'اختبار حذف بند',
        ]);

        $item = $quotation->items()->create([
            'description' => 'خرسانة جاهزة للحذف',
            'quantity' => 3,
            'unit_price' => 300,
            'line_total' => 900,
        ]);

        $response = $this->actingAs($user)->delete('/quotations/' . $quotation->id . '/items/' . $item->id);

        $response->assertRedirect('/quotations/' . $quotation->id);

        $this->assertDatabaseMissing('quotation_items', [
            'id' => $item->id,
        ]);
    }


    public function test_quotation_total_updates_after_add_update_and_delete_items(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل إجمالي عرض السعر',
            'phone' => '0555555555',
            'email' => 'quotation-total@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'notes' => 'اختبار إجمالي عرض السعر',
        ]);

        $this->actingAs($user)->post('/quotations/' . $quotation->id . '/items', [
            'description' => 'بند إجمالي أول',
            'quantity' => 4,
            'unit_price' => 100,
        ])->assertRedirect('/quotations/' . $quotation->id);

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'total_amount' => 400,
        ]);

        $item = $quotation->items()->first();

        $this->actingAs($user)->patch('/quotations/' . $quotation->id . '/items/' . $item->id, [
            'description' => 'بند إجمالي معدل',
            'quantity' => 6,
            'unit_price' => 150,
        ])->assertRedirect('/quotations/' . $quotation->id);

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'total_amount' => 900,
        ]);

        $this->actingAs($user)->delete('/quotations/' . $quotation->id . '/items/' . $item->id)
            ->assertRedirect('/quotations/' . $quotation->id);

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'total_amount' => 0,
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
