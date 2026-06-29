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

class CustomerSupplierBulkStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    private ?int $currentCompanyId = null;
    private ?int $currentBranchId = null;

    private function signIn(): User
    {
        $companyId = $this->createCompanyId();
        $branchId = $this->createBranchId($companyId);

        $this->currentCompanyId = $companyId;
        $this->currentBranchId = $branchId;

        $user = $this->createTestUser($companyId, $branchId);

        $this->actingAs($user);

        return $user;
    }

    private function createTestUser(?int $companyId = null, ?int $branchId = null): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Owner Bulk Status Test',
            'email' => 'owner-bulk-status-test@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId ?? $this->createBranchId($data['company_id'] ?? null);
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

        if ($this->currentCompanyId) {
            return $this->currentCompanyId;
        }

        $existing = DB::table('companies')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('companies');

        $data = [
            'name' => 'شركة اختبار التحديث الجماعي',
            'commercial_name' => 'شركة اختبار التحديث الجماعي',
            'email' => 'company-bulk-status-test@example.com',
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

        if ($this->currentBranchId) {
            return $this->currentBranchId;
        }

        $existing = DB::table('branches')->value('id');

        if ($existing) {
            return (int) $existing;
        }

        $columns = Schema::getColumnListing('branches');

        $data = [
            'name' => 'فرع اختبار التحديث الجماعي',
            'code' => 'BULK-STATUS',
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

        $unique = (string) random_int(100000, 999999);

        $data = [
            'name' => 'عميل تحديث جماعي ' . $unique,
            'phone' => '0559' . $unique,
            'email' => 'customer-bulk-' . $unique . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->currentCompanyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->currentBranchId ?? $this->createBranchId($data['company_id'] ?? null);
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

        $unique = (string) random_int(100000, 999999);

        $data = [
            'name' => 'مورد تحديث جماعي ' . $unique,
            'phone' => '0569' . $unique,
            'email' => 'supplier-bulk-' . $unique . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->currentCompanyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->currentBranchId ?? $this->createBranchId($data['company_id'] ?? null);
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

    public function test_customers_can_be_bulk_deactivated(): void
    {
        $this->signIn();

        $first = $this->createCustomer(['is_active' => true]);
        $second = $this->createCustomer(['is_active' => true]);

        $response = $this->patch(route('customers.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $first->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $second->id,
            'is_active' => false,
        ]);
    }

    public function test_customers_can_be_bulk_activated(): void
    {
        $this->signIn();

        $first = $this->createCustomer(['is_active' => false]);
        $second = $this->createCustomer(['is_active' => false]);

        $response = $this->patch(route('customers.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $first->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $second->id,
            'is_active' => true,
        ]);
    }

    public function test_suppliers_can_be_bulk_deactivated(): void
    {
        $this->signIn();

        $first = $this->createSupplier(['is_active' => true]);
        $second = $this->createSupplier(['is_active' => true]);

        $response = $this->patch(route('suppliers.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'is_active' => '0',
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $first->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $second->id,
            'is_active' => false,
        ]);
    }

    public function test_suppliers_can_be_bulk_activated(): void
    {
        $this->signIn();

        $first = $this->createSupplier(['is_active' => false]);
        $second = $this->createSupplier(['is_active' => false]);

        $response = $this->patch(route('suppliers.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $first->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('suppliers', [
            'id' => $second->id,
            'is_active' => true,
        ]);
    }

    public function test_customer_and_supplier_indexes_have_bulk_status_forms(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerResponse = $this->get(route('customers.index'));
        $supplierResponse = $this->get(route('suppliers.index'));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.bulk-status'), false);
        $customerResponse->assertSee('data-testid="customers-bulk-status-form"', false);
        $customerResponse->assertSee('data-testid="customers-bulk-checkbox-' . $customer->id . '"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.bulk-status'), false);
        $supplierResponse->assertSee('data-testid="suppliers-bulk-status-form"', false);
        $supplierResponse->assertSee('data-testid="suppliers-bulk-checkbox-' . $supplier->id . '"', false);
    }
}
