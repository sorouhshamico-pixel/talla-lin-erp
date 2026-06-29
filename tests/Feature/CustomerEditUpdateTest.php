<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerEditUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_customer_edit_page_displays_existing_customer_data(): void
    {
        $this->actingAsOwner();

        $customerId = $this->insertCustomer([
            'name' => 'عميل قابل للتعديل',
            'phone' => '0530000001',
            'email' => 'editable@example.com',
            'city' => 'الرياض',
            'address' => 'عنوان قديم',
            'tax_number' => '355555555555555',
            'is_active' => true,
        ]);

        $response = $this->get(route('customers.edit', $customerId));

        $response->assertOk();

        $response->assertSee('data-testid="customers-edit"', false);
        $response->assertSee('data-testid="customers-edit-form"', false);
        $response->assertSee('عميل قابل للتعديل');
        $response->assertSee('editable@example.com');
        $response->assertSee('355555555555555');
    }

    public function test_customer_can_be_updated(): void
    {
        $this->actingAsOwner();

        $customerId = $this->insertCustomer([
            'name' => 'عميل قبل التحديث',
            'phone' => '0530000002',
            'email' => 'before@example.com',
            'city' => 'جدة',
            'address' => 'عنوان قبل التحديث',
            'tax_number' => '366666666666666',
            'is_active' => true,
        ]);

        $response = $this->put(route('customers.update', $customerId), [
            'name' => 'عميل بعد التحديث',
            'phone' => '0539999999',
            'email' => 'after@example.com',
            'city' => 'الرياض',
            'address' => 'عنوان بعد التحديث',
            'tax_number' => '377777777777777',
            'is_active' => '0',
        ]);

        $response
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', 'تم تحديث العميل بنجاح.');

        $this->assertDatabaseHas('customers', [
            'id' => $customerId,
            'name' => 'عميل بعد التحديث',
            'phone' => '0539999999',
            'email' => 'after@example.com',
            'city' => 'الرياض',
            'address' => 'عنوان بعد التحديث',
            'tax_number' => '377777777777777',
            'is_active' => false,
        ]);
    }

    public function test_customer_name_is_required_when_updating(): void
    {
        $this->actingAsOwner();

        $customerId = $this->insertCustomer([
            'name' => 'عميل للتحقق',
            'phone' => '0530000003',
            'email' => 'validate@example.com',
            'city' => 'الرياض',
            'address' => 'عنوان',
            'tax_number' => '344444444444444',
            'is_active' => true,
        ]);

        $response = $this->from(route('customers.edit', $customerId))
            ->put(route('customers.update', $customerId), [
                'name' => '',
                'phone' => '0530000003',
                'email' => 'validate@example.com',
                'city' => 'الرياض',
                'address' => 'عنوان',
                'tax_number' => '344444444444444',
                'is_active' => '1',
            ]);

        $response
            ->assertRedirect(route('customers.edit', $customerId))
            ->assertSessionHasErrors('name');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertCustomer(array $overrides): int
    {
        $now = now();

        return (int) DB::table('customers')->insertGetId([
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
