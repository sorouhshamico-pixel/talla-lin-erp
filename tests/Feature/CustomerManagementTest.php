<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customers_index_displays_customers_and_summary(): void
    {
        $this->actingAsOwner();


        $this->insertCustomer([
            'name' => 'شركة الرياض للتوريد',
            'phone' => '0500000001',
            'email' => 'riyadh@example.com',
            'city' => 'الرياض',
            'tax_number' => '300000000000001',
            'is_active' => true,
        ]);

        $this->insertCustomer([
            'name' => 'مؤسسة جدة التجارية',
            'phone' => '0500000002',
            'email' => 'jeddah@example.com',
            'city' => 'جدة',
            'tax_number' => '300000000000002',
            'is_active' => false,
        ]);

        $response = $this->get(route('customers.index'));

        $response->assertOk();

        $response->assertSee('data-testid="customers-index"', false);
        $response->assertSee('شركة الرياض للتوريد');
        $response->assertSee('مؤسسة جدة التجارية');
        $response->assertSee('data-testid="customers-summary-total"', false);
        $response->assertSee('data-testid="customers-summary-active"', false);
        $response->assertSee('data-testid="customers-summary-inactive"', false);
        $response->assertSee('data-testid="customers-create-link"', false);
    }

    public function test_customers_index_can_filter_by_search_and_status(): void
    {
        $this->actingAsOwner();


        $this->insertCustomer([
            'name' => 'شركة الرياض للتوريد',
            'phone' => '0500000001',
            'email' => 'riyadh@example.com',
            'city' => 'الرياض',
            'tax_number' => '300000000000001',
            'is_active' => true,
        ]);

        $this->insertCustomer([
            'name' => 'مؤسسة جدة التجارية',
            'phone' => '0500000002',
            'email' => 'jeddah@example.com',
            'city' => 'جدة',
            'tax_number' => '300000000000002',
            'is_active' => false,
        ]);

        $response = $this->get(route('customers.index', [
            'q' => 'الرياض',
            'is_active' => '1',
        ]));

        $response->assertOk();

        $response->assertSee('شركة الرياض للتوريد');
        $response->assertDontSee('مؤسسة جدة التجارية');
    }

    public function test_customer_create_page_is_available(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('customers.create'));

        $response->assertOk();

        $response->assertSee('data-testid="customers-create"', false);
        $response->assertSee('data-testid="customers-create-form"', false);
        $response->assertSee('إضافة عميل');
    }

    public function test_customer_can_be_created(): void
    {
        $this->actingAsOwner();

        $response = $this->post(route('customers.store'), [
            'name' => 'شركة اختبار العملاء',
            'phone' => '0511111111',
            'email' => 'customer@example.com',
            'city' => 'الرياض',
            'address' => 'عنوان اختبار',
            'tax_number' => '399999999999999',
            'is_active' => '1',
        ]);

        $response
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', 'تم إضافة العميل بنجاح.');

        $this->assertDatabaseHas('customers', [
            'name' => 'شركة اختبار العملاء',
            'phone' => '0511111111',
            'email' => 'customer@example.com',
            'city' => 'الرياض',
            'tax_number' => '399999999999999',
            'is_active' => true,
        ]);
    }

    public function test_customer_name_is_required(): void
    {
        $this->actingAsOwner();

        $response = $this->from(route('customers.create'))
            ->post(route('customers.store'), [
                'name' => '',
                'email' => 'customer@example.com',
                'is_active' => '1',
            ]);

        $response
            ->assertRedirect(route('customers.create'))
            ->assertSessionHasErrors('name');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertCustomer(array $overrides): void
    {
        $now = now();

        DB::table('customers')->insert([
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
