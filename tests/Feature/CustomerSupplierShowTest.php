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

class CustomerSupplierShowTest extends TestCase
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
            'name' => 'Owner Show Test User',
            'email' => 'owner-show-test@example.com',
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
            'name' => 'شركة اختبار صفحات التفاصيل',
            'commercial_name' => 'شركة اختبار صفحات التفاصيل',
            'email' => 'company-show-test@example.com',
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
            'name' => 'فرع اختبار صفحات التفاصيل',
            'code' => 'SHOW-TEST',
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
            'name' => 'عميل صفحة التفاصيل',
            'phone' => '0551111111',
            'email' => 'customer-show@example.com',
            'vat_number' => '300000000000010',
            'tax_number' => '300000000000010',
            'commercial_registration' => '1010000010',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'عميل خاص باختبار صفحة التفاصيل',
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
            'name' => 'مورد صفحة التفاصيل',
            'contact_name' => 'مسؤول المورد',
            'contact_person' => 'مسؤول المورد',
            'phone' => '0552222222',
            'email' => 'supplier-show@example.com',
            'vat_number' => '300000000000020',
            'tax_number' => '300000000000020',
            'commercial_registration' => '1010000020',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'مورد خاص باختبار صفحة التفاصيل',
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

    public function test_customer_show_page_can_be_opened(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('تفاصيل العميل');
        $response->assertSee($customer->name);
        $response->assertSee($customer->phone);
        $response->assertSee(route('customers.edit', $customer), false);
    }

    public function test_supplier_show_page_can_be_opened(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->get(route('suppliers.show', $supplier));

        $response->assertOk();
        $response->assertSee('تفاصيل المورد');
        $response->assertSee($supplier->name);
        $response->assertSee($supplier->phone);
        $response->assertSee(route('suppliers.edit', $supplier), false);
    }

    public function test_customers_index_has_show_link(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee(route('customers.show', $customer), false);
    }

    public function test_suppliers_index_has_show_link(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertSee(route('suppliers.show', $supplier), false);
    }
}
