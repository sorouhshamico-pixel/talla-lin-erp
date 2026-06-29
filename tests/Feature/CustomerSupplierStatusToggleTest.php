<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierStatusToggleTest extends TestCase
{
    use RefreshDatabase;

    private function signIn(): User
    {
        $user = $this->createTestUser();

        $this->actingAs($user);

        return $user;
    }

    private function createTestUser(): User
    {
        $columns = Schema::getColumnListing('users');

        $companyId = in_array('company_id', $columns, true)
            ? $this->createCompanyId()
            : null;

        $branchId = in_array('branch_id', $columns, true)
            ? $this->createBranchId($companyId)
            : null;

        $data = [
            'name' => 'Owner Status Toggle Test',
            'email' => 'owner-status-toggle-test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId;
        }

        foreach (['role', 'type', 'user_type'] as $field) {
            if (in_array($field, $columns, true)) {
                $data[$field] = 'owner';
            }
        }

        foreach (['is_active', 'active'] as $field) {
            if (in_array($field, $columns, true)) {
                $data[$field] = true;
            }
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('users', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return User::unguarded(fn () => User::query()->create($data));
    }

    private function createCompanyId(): ?int
    {
        if (! Schema::hasTable('companies')) {
            return null;
        }

        $existing = DB::table('companies')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('companies');

        $data = [
            'name' => 'شركة اختبار تفعيل العملاء والموردين',
            'commercial_name' => 'شركة اختبار تفعيل العملاء والموردين',
            'email' => 'company-status-toggle-test@example.com',
            'phone' => '0500000000',
            'tax_number' => '300000000000001',
            'vat_number' => '300000000000001',
            'commercial_registration' => '1010000000',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('companies', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return (int) DB::table('companies')->insertGetId($data);
    }

    private function createBranchId(?int $companyId = null): ?int
    {
        if (! Schema::hasTable('branches')) {
            return null;
        }

        $existing = DB::table('branches')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('branches');

        $data = [
            'name' => 'فرع اختبار تفعيل العملاء والموردين',
            'code' => 'STATUS-TOGGLE',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'phone' => '0500000000',
            'is_active' => true,
        ];

        if ($companyId && in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('branches', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return (int) DB::table('branches')->insertGetId($data);
    }

    private function fillRequiredColumns(string $table, array $data): array
    {
        foreach (DB::select("PRAGMA table_info({$table})") as $column) {
            if ((int) $column->pk === 1) {
                continue;
            }

            if ((int) $column->notnull !== 1) {
                continue;
            }

            if ($column->dflt_value !== null) {
                continue;
            }

            if (array_key_exists($column->name, $data)) {
                continue;
            }

            $columnName = strtolower($column->name);
            $columnType = strtoupper((string) $column->type);

            $data[$column->name] = match (true) {
                str_contains($columnName, 'company_id') => $this->createCompanyId(),
                str_contains($columnName, 'branch_id') => $this->createBranchId($data['company_id'] ?? null),
                str_contains($columnName, 'email') => $table . '-required@example.com',
                str_contains($columnName, 'password') => Hash::make('password'),
                str_contains($columnName, 'phone') => '0500000000',
                str_contains($columnName, 'active') => true,
                str_contains($columnName, 'role') => 'owner',
                str_contains($columnName, 'type') => 'owner',
                str_contains($columnName, 'date') => now()->toDateString(),
                str_contains($columnType, 'INT') => 1,
                str_contains($columnType, 'REAL') => 1,
                str_contains($columnType, 'NUM') => 1,
                default => 'اختبار',
            };
        }

        return $data;
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = [
            'name' => 'عميل اختبار التفعيل',
            'phone' => '0551111111',
            'email' => 'customer-status-toggle@example.com',
            'vat_number' => '300000000000010',
            'tax_number' => '300000000000010',
            'commercial_registration' => '1010000010',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'عميل خاص باختبار التفعيل',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->createBranchId($data['company_id'] ?? null);
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('customers', $data);
        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Customer::unguarded(fn () => Customer::query()->create($data));
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'name' => 'مورد اختبار التفعيل',
            'contact_name' => 'مسؤول المورد',
            'contact_person' => 'مسؤول المورد',
            'phone' => '0552222222',
            'email' => 'supplier-status-toggle@example.com',
            'vat_number' => '300000000000020',
            'tax_number' => '300000000000020',
            'commercial_registration' => '1010000020',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'مورد خاص باختبار التفعيل',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->createBranchId($data['company_id'] ?? null);
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('suppliers', $data);
        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    public function test_customer_status_can_be_toggled_from_active_to_inactive(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['is_active' => true]);

        $response = $this->patch(route('customers.toggle-active', $customer));

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => false,
        ]);
    }

    public function test_customer_status_can_be_toggled_from_inactive_to_active(): void
    {
        $this->signIn();

        $customer = $this->createCustomer(['is_active' => false]);

        $response = $this->patch(route('customers.toggle-active', $customer));

        $response->assertRedirect(route('customers.show', $customer));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'is_active' => true,
        ]);
    }

    public function test_supplier_status_can_be_toggled_from_active_to_inactive(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier(['is_active' => true]);

        $response = $this->patch(route('suppliers.toggle-active', $supplier));

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => false,
        ]);
    }

    public function test_supplier_status_can_be_toggled_from_inactive_to_active(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier(['is_active' => false]);

        $response = $this->patch(route('suppliers.toggle-active', $supplier));

        $response->assertRedirect(route('suppliers.show', $supplier));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'is_active' => true,
        ]);
    }

    public function test_show_pages_have_status_toggle_forms(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->get(route('customers.show', $customer));
        $supplierResponse = $this->get(route('suppliers.show', $supplier));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.toggle-active', $customer), false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.toggle-active', $supplier), false);
    }
}
