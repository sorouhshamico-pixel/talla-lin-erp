<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PartyPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private ?int $currentCompanyId = null;
    private ?int $currentBranchId = null;

    private function signIn(string $role = 'owner'): User
    {
        $companyId = $this->createCompanyId();
        $branchId = $this->createBranchId($companyId);

        $this->currentCompanyId = $companyId;
        $this->currentBranchId = $branchId;

        $user = $this->createTestUser($companyId, $branchId, $role);

        $this->actingAs($user);

        return $user;
    }

    private function createTestUser(?int $companyId = null, ?int $branchId = null, string $role = 'owner'): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Owner Access Control Test ' . uniqid(),
            'email' => 'owner-access-control-' . uniqid() . '@example.com',
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
                $data[$field] = $role;
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
            'name' => 'شركة اختبار الصلاحيات',
            'commercial_name' => 'شركة اختبار الصلاحيات',
            'email' => 'company-access-control-test@example.com',
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
            'name' => 'فرع اختبار الصلاحيات',
            'code' => 'ACCESS-CONTROL',
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
                str_contains($columnName, 'user_id') => 1,
                str_contains($columnName, 'email') => $table . '-required-' . uniqid() . '@example.com',
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
            'name' => 'عميل اختبار الصلاحيات',
            'phone' => '0559100001',
            'email' => 'customer-access-control-' . uniqid() . '@example.com',
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

        $data = [
            'name' => 'مورد اختبار الصلاحيات',
            'phone' => '0569100001',
            'email' => 'supplier-access-control-' . uniqid() . '@example.com',
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

    public function test_owner_can_access_party_permissions_page(): void
    {
        $this->signIn('owner');

        $response = $this->get(route('party-permissions.index'));

        $response->assertOk();
        $response->assertSee('صلاحيات العملاء والموردين');
        $response->assertSee('data-testid="party-permissions-table"', false);
        $response->assertSee('owner');
        $response->assertSee('admin');
    }

    public function test_viewer_can_view_customers_and_suppliers_but_cannot_access_permissions_page(): void
    {
        $this->signIn('viewer');

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $this->get(route('customers.index'))->assertOk();
        $this->get(route('customers.show', $customer))->assertOk();

        $this->get(route('suppliers.index'))->assertOk();
        $this->get(route('suppliers.show', $supplier))->assertOk();

        $this->get(route('party-permissions.index'))->assertForbidden();
    }

    public function test_viewer_cannot_create_or_edit_customer_or_supplier(): void
    {
        $this->signIn('viewer');

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $this->get(route('customers.create'))->assertForbidden();
        $this->get(route('customers.edit', $customer))->assertForbidden();

        $this->get(route('suppliers.create'))->assertForbidden();
        $this->get(route('suppliers.edit', $supplier))->assertForbidden();
    }

    public function test_accountant_can_view_financial_pages_and_exports_but_cannot_manage_parties(): void
    {
        $this->signIn('accountant');

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $this->get(route('customers.statement', $customer))->assertOk();
        $this->get(route('suppliers.statement', $supplier))->assertOk();

        $this->get(route('customers.statement.export', $customer))->assertOk();
        $this->get(route('suppliers.statement.export', $supplier))->assertOk();

        $this->get(route('customers.create'))->assertForbidden();
        $this->get(route('suppliers.create'))->assertForbidden();
    }

    public function test_permission_service_resolves_role_permissions(): void
    {
        $owner = $this->createTestUser(role: 'owner');
        $viewer = $this->createTestUser(role: 'viewer');

        $service = app(PartyPermissionService::class);

        $this->assertTrue($service->can($owner, PartyPermissionService::MANAGE_PARTIES));
        $this->assertTrue($service->can($owner, PartyPermissionService::EXPORT_PARTIES));

        $this->assertTrue($service->can($viewer, PartyPermissionService::VIEW_PARTIES));
        $this->assertFalse($service->can($viewer, PartyPermissionService::MANAGE_PARTIES));
        $this->assertFalse($service->can($viewer, PartyPermissionService::EXPORT_PARTIES));
    }

    public function test_customer_and_supplier_index_pages_link_to_permission_reference(): void
    {
        $this->signIn('owner');

        $customersResponse = $this->get(route('customers.index'));
        $suppliersResponse = $this->get(route('suppliers.index'));

        $customersResponse->assertOk();
        $customersResponse->assertSee(route('party-permissions.index'), false);
        $customersResponse->assertSee('data-testid="customers-permissions-link"', false);

        $suppliersResponse->assertOk();
        $suppliersResponse->assertSee(route('party-permissions.index'), false);
        $suppliersResponse->assertSee('data-testid="suppliers-permissions-link"', false);
    }
}
