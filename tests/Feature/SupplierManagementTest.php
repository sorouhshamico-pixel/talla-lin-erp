<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_suppliers_index_displays_suppliers_and_summary(): void
    {
        $this->actingAsOwner();

        $this->insertSupplier([
            'name' => 'شركة الدمام للمواد',
            'phone' => '0520000001',
            'email' => 'dammam@example.com',
            'city' => 'الدمام',
            'tax_number' => '311111111111111',
            'is_active' => true,
        ]);

        $this->insertSupplier([
            'name' => 'مؤسسة مكة للتوريد',
            'phone' => '0520000002',
            'email' => 'makkah@example.com',
            'city' => 'مكة',
            'tax_number' => '322222222222222',
            'is_active' => false,
        ]);

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();

        $response->assertSee('data-testid="suppliers-index"', false);
        $response->assertSee('شركة الدمام للمواد');
        $response->assertSee('مؤسسة مكة للتوريد');
        $response->assertSee('data-testid="suppliers-summary-total"', false);
        $response->assertSee('data-testid="suppliers-summary-active"', false);
        $response->assertSee('data-testid="suppliers-summary-inactive"', false);
        $response->assertSee('data-testid="suppliers-create-link"', false);
    }

    public function test_suppliers_index_can_filter_by_search_and_status(): void
    {
        $this->actingAsOwner();

        $this->insertSupplier([
            'name' => 'شركة الدمام للمواد',
            'phone' => '0520000001',
            'email' => 'dammam@example.com',
            'city' => 'الدمام',
            'tax_number' => '311111111111111',
            'is_active' => true,
        ]);

        $this->insertSupplier([
            'name' => 'مؤسسة مكة للتوريد',
            'phone' => '0520000002',
            'email' => 'makkah@example.com',
            'city' => 'مكة',
            'tax_number' => '322222222222222',
            'is_active' => false,
        ]);

        $response = $this->get(route('suppliers.index', [
            'q' => 'الدمام',
            'is_active' => '1',
        ]));

        $response->assertOk();

        $response->assertSee('شركة الدمام للمواد');
        $response->assertDontSee('مؤسسة مكة للتوريد');
    }

    public function test_supplier_create_page_is_available(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('suppliers.create'));

        $response->assertOk();

        $response->assertSee('data-testid="suppliers-create"', false);
        $response->assertSee('data-testid="suppliers-create-form"', false);
        $response->assertSee('إضافة مورد');
    }

    public function test_supplier_can_be_created(): void
    {
        $this->actingAsOwner();

        $response = $this->post(route('suppliers.store'), [
            'name' => 'شركة اختبار الموردين',
            'phone' => '0522222222',
            'email' => 'supplier@example.com',
            'city' => 'الرياض',
            'address' => 'عنوان مورد اختبار',
            'tax_number' => '388888888888888',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('status', 'تم إضافة المورد بنجاح.');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'شركة اختبار الموردين',
            'phone' => '0522222222',
            'email' => 'supplier@example.com',
            'city' => 'الرياض',
            'tax_number' => '388888888888888',
            'is_active' => true,
        ]);
    }

    public function test_supplier_name_is_required(): void
    {
        $this->actingAsOwner();

        $response = $this->from(route('suppliers.create'))
            ->post(route('suppliers.store'), [
                'name' => '',
                'email' => 'supplier@example.com',
                'is_active' => '1',
            ]);

        $response
            ->assertRedirect(route('suppliers.create'))
            ->assertSessionHasErrors('name');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertSupplier(array $overrides): void
    {
        $now = now();

        DB::table('suppliers')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'name' => $overrides['name'],
            'phone' => $overrides['phone'],
            'email' => $overrides['email'],
            'city' => $overrides['city'],
            'address' => $overrides['address'] ?? null,
            'tax_number' => $overrides['tax_number'],
            'is_active' => $overrides['is_active'],
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
