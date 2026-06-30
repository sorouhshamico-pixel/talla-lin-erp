<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PartyContactLog;
use App\Models\PartyTag;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PartyDashboardSummaryService;
use App\Services\PartyDuplicateService;
use App\Services\PartyFinancialSummaryService;
use App\Services\PartyPermissionService;
use App\Services\PartyStatementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierModuleFinalSmokeTest extends TestCase
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

        $user = $this->createUserRecord($companyId, $branchId, $role);

        $this->actingAs($user);

        return $user;
    }

    private function createUserRecord(?int $companyId, ?int $branchId, string $role): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Final Smoke User ' . uniqid(),
            'email' => 'final-smoke-user-' . uniqid() . '@example.com',
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
                $data[$field] = $role;
            }
        }

        foreach (['is_active', 'active'] as $field) {
            if (in_array($field, $columns, true)) {
                $data[$field] = true;
            }
        }

        $data = $this->addTimestamps('users', $data);
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
            'name' => 'شركة اختبار إغلاق وحدة العملاء والموردين',
            'commercial_name' => 'شركة اختبار إغلاق وحدة العملاء والموردين',
            'email' => 'company-final-smoke@example.com',
            'phone' => '0500000000',
            'tax_number' => '300000000000001',
            'vat_number' => '300000000000001',
            'commercial_registration' => '1010000000',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        $data = $this->addTimestamps('companies', $data);
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
            'name' => 'فرع اختبار إغلاق وحدة العملاء والموردين',
            'code' => 'PARTY-FINAL-SMOKE',
            'city' => 'الرياض',
            'address' => 'الرياض',
            'phone' => '0500000000',
            'is_active' => true,
        ];

        if ($companyId && in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        $data = $this->addTimestamps('branches', $data);
        $data = $this->fillRequiredColumns('branches', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return (int) DB::table('branches')->insertGetId($data);
    }

    private function createCustomer(array $overrides = []): Customer
    {
        $columns = Schema::getColumnListing('customers');

        $data = [
            'name' => 'عميل اختبار إغلاق الوحدة ' . uniqid(),
            'phone' => '055' . random_int(1000000, 9999999),
            'email' => 'customer-final-smoke-' . uniqid() . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->currentCompanyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->currentBranchId ?? $this->createBranchId($data['company_id'] ?? null);
        }

        $data = $this->addTimestamps('customers', $data);
        $data = $this->fillRequiredColumns('customers', $data);
        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Customer::unguarded(fn () => Customer::query()->create($data));
    }

    private function createSupplier(array $overrides = []): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'name' => 'مورد اختبار إغلاق الوحدة ' . uniqid(),
            'phone' => '056' . random_int(1000000, 9999999),
            'email' => 'supplier-final-smoke-' . uniqid() . '@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->currentCompanyId ?? $this->createCompanyId();
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $this->currentBranchId ?? $this->createBranchId($data['company_id'] ?? null);
        }

        $data = $this->addTimestamps('suppliers', $data);
        $data = $this->fillRequiredColumns('suppliers', $data);
        $data = array_intersect_key($data, array_flip($columns));
        $data = array_merge($data, $overrides);

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    private function addTimestamps(string $table, array $data): array
    {
        $columns = Schema::getColumnListing($table);

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        return $data;
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

    public function test_final_customer_supplier_module_routes_are_registered(): void
    {
        $routeNames = [
            'customers.index',
            'customers.show',
            'customers.activity-timeline.index',
            'customers.statement',
            'customers.statement.export',
            'suppliers.index',
            'suppliers.show',
            'suppliers.activity-timeline.index',
            'suppliers.statement',
            'suppliers.statement.export',
            'party-follow-ups.index',
            'party-dashboard.index',
            'party-tags.index',
            'party-duplicates.index',
            'party-permissions.index',
        ];

        foreach ($routeNames as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route: {$routeName}");
        }
    }

    public function test_final_customer_supplier_module_services_return_expected_structures(): void
    {
        $this->signIn('owner');

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $customerFinancial = app(PartyFinancialSummaryService::class)->customerSummary($customer->id);
        $supplierFinancial = app(PartyFinancialSummaryService::class)->supplierSummary($supplier->id);

        foreach (['count', 'total', 'paid', 'pending'] as $key) {
            $this->assertArrayHasKey($key, $customerFinancial);
            $this->assertArrayHasKey($key, $supplierFinancial);
        }

        $customerStatement = app(PartyStatementService::class)->customerStatement($customer->id);
        $supplierStatement = app(PartyStatementService::class)->supplierStatement($supplier->id);

        foreach (['rows', 'total_debit', 'total_credit', 'balance', 'count'] as $key) {
            $this->assertArrayHasKey($key, $customerStatement);
            $this->assertArrayHasKey($key, $supplierStatement);
        }

        $duplicateGroups = app(PartyDuplicateService::class)->allGroups();

        foreach (['customer_phone', 'customer_email', 'supplier_phone', 'supplier_email'] as $key) {
            $this->assertArrayHasKey($key, $duplicateGroups);
        }

        $permissions = app(PartyPermissionService::class)->permissions();

        foreach ([
            PartyPermissionService::VIEW_PARTIES,
            PartyPermissionService::MANAGE_PARTIES,
            PartyPermissionService::EXPORT_PARTIES,
            PartyPermissionService::VIEW_PARTY_FINANCIALS,
        ] as $permission) {
            $this->assertArrayHasKey($permission, $permissions);
        }

        $dashboardSummary = app(PartyDashboardSummaryService::class)->summary();

        foreach ([
            'customers_total',
            'customers_active',
            'suppliers_total',
            'suppliers_active',
            'follow_ups_due',
            'follow_ups_upcoming',
            'follow_ups_completed',
            'party_tags_total',
            'duplicate_groups_total',
        ] as $key) {
            $this->assertArrayHasKey($key, $dashboardSummary);
        }
    }

    public function test_final_customer_supplier_module_exports_still_work(): void
    {
        $this->signIn('owner');

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $this->get(route('customers.statement.export', $customer))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get(route('suppliers.statement.export', $supplier))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_final_customer_supplier_module_viewer_access_is_still_limited(): void
    {
        $this->signIn('viewer');

        $customer = $this->createCustomer();
        $supplier = $this->createSupplier();

        $this->get(route('customers.index'))->assertOk();
        $this->get(route('customers.show', $customer))->assertOk();
        $this->get(route('suppliers.index'))->assertOk();
        $this->get(route('suppliers.show', $supplier))->assertOk();
        $this->get(route('party-dashboard.index'))->assertOk();

        $this->get(route('customers.create'))->assertForbidden();
        $this->get(route('suppliers.create'))->assertForbidden();
        $this->get(route('party-permissions.index'))->assertForbidden();
    }
}
