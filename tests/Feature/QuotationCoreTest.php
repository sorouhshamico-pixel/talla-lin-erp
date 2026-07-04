<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QuotationCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotations_index_page_can_be_viewed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/quotations');

        $response->assertOk();
        $response->assertSee('عروض الأسعار');
    }

    public function test_quotation_create_page_can_be_viewed(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/quotations/create');

        $response->assertOk();
        $response->assertSee('إنشاء عرض سعر');
    }

    public function test_user_can_create_quotation_with_generated_number_and_draft_status(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل اختبار',
            'phone' => '0500000000',
            'email' => 'customer@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post('/quotations', [
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'notes' => 'عرض سعر تجريبي',
        ]);

        $quotation = Quotation::query()->first();

        $this->assertNotNull($quotation);
        $this->assertSame('QT-000001', $quotation->quotation_number);
        $this->assertSame('draft', $quotation->status);
        $this->assertSame($customer->id, $quotation->customer_id);

        $response->assertRedirect('/quotations/' . $quotation->id);
    }

    public function test_quotation_show_page_can_be_viewed(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'company_id' => $this->createCompanyId(),
            'name' => 'عميل صفحة التفاصيل',
            'phone' => '0511111111',
            'email' => 'show@example.com',
            'address' => 'الرياض',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'QT-000001',
            'customer_id' => $customer->id,
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'notes' => 'تفاصيل عرض السعر',
        ]);

        $response = $this->actingAs($user)->get('/quotations/' . $quotation->id);

        $response->assertOk();
        $response->assertSee('QT-000001');
        $response->assertSee('draft');
        $response->assertSee('عميل صفحة التفاصيل');
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
