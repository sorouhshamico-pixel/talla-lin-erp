<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PartyDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierDuplicateDetectionTest extends TestCase
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
            'name' => 'Owner Duplicate Detection Test',
            'email' => 'owner-duplicate-detection-test@example.com',
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
            'name' => 'شركة اختبار التكرارات',
            'commercial_name' => 'شركة اختبار التكرارات',
            'email' => 'company-duplicate-detection-test@example.com',
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
            'name' => 'فرع اختبار التكرارات',
            'code' => 'DUPLICATE-DETECTION',
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
                str_contains($columnName, 'phone') => '050' . random_int(1000000, 9999999),
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

        $unique = uniqid();

        $data = [
            'name' => 'عميل كشف التكرار ' . $unique,
            'phone' => '055' . random_int(1000000, 9999999),
            'email' => 'customer-duplicate-' . $unique . '@example.com',
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

        $unique = uniqid();

        $data = [
            'name' => 'مورد كشف التكرار ' . $unique,
            'phone' => '056' . random_int(1000000, 9999999),
            'email' => 'supplier-duplicate-' . $unique . '@example.com',
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

    public function test_duplicate_center_displays_customer_and_supplier_duplicate_groups(): void
    {
        $this->signIn();

        $customerA = $this->createCustomer([
            'name' => 'عميل مكرر أول',
            'phone' => '0555000001',
            'email' => 'customer-shared@example.com',
        ]);

        $customerB = $this->createCustomer([
            'name' => 'عميل مكرر ثاني',
            'phone' => '+966555000001',
            'email' => 'CUSTOMER-SHARED@example.com',
        ]);

        $supplierA = $this->createSupplier([
            'name' => 'مورد مكرر أول',
            'phone' => '0566000001',
            'email' => 'supplier-shared@example.com',
        ]);

        $supplierB = $this->createSupplier([
            'name' => 'مورد مكرر ثاني',
            'phone' => '+966566000001',
            'email' => 'SUPPLIER-SHARED@example.com',
        ]);

        $response = $this->get(route('party-duplicates.index'));

        $response->assertOk();
        $response->assertSee('مركز كشف التكرارات');
        $response->assertSee('عميل مكرر أول');
        $response->assertSee('عميل مكرر ثاني');
        $response->assertSee('مورد مكرر أول');
        $response->assertSee('مورد مكرر ثاني');
        $response->assertSee(route('customers.show', $customerA), false);
        $response->assertSee(route('customers.show', $customerB), false);
        $response->assertSee(route('suppliers.show', $supplierA), false);
        $response->assertSee(route('suppliers.show', $supplierB), false);
        $response->assertSee('data-testid="duplicate-section-customer_phone"', false);
        $response->assertSee('data-testid="duplicate-section-supplier_email"', false);
    }

    public function test_customer_show_page_displays_duplicate_warning_when_duplicate_exists(): void
    {
        $this->signIn();

        $customerA = $this->createCustomer([
            'name' => 'عميل أصلي للتكرار',
            'phone' => '0557000001',
            'email' => 'customer-show-duplicate@example.com',
        ]);

        $this->createCustomer([
            'name' => 'عميل مشابه للتكرار',
            'phone' => '+966557000001',
            'email' => 'different-customer-show@example.com',
        ]);

        $response = $this->get(route('customers.show', $customerA));

        $response->assertOk();
        $response->assertSee('فحص تكرار العميل');
        $response->assertSee('يوجد');
        $response->assertSee('عميل مشابه للتكرار');
        $response->assertSee(route('party-duplicates.index'), false);
        $response->assertSee('data-testid="customers-duplicate-warning-card"', false);
        $response->assertSee('data-testid="customers-duplicate-warning-message"', false);
    }

    public function test_supplier_show_page_displays_duplicate_warning_when_duplicate_exists(): void
    {
        $this->signIn();

        $supplierA = $this->createSupplier([
            'name' => 'مورد أصلي للتكرار',
            'phone' => '0567000001',
            'email' => 'supplier-show-duplicate@example.com',
        ]);

        $this->createSupplier([
            'name' => 'مورد مشابه للتكرار',
            'phone' => '+966567000001',
            'email' => 'different-supplier-show@example.com',
        ]);

        $response = $this->get(route('suppliers.show', $supplierA));

        $response->assertOk();
        $response->assertSee('فحص تكرار المورد');
        $response->assertSee('يوجد');
        $response->assertSee('مورد مشابه للتكرار');
        $response->assertSee(route('party-duplicates.index'), false);
        $response->assertSee('data-testid="suppliers-duplicate-warning-card"', false);
        $response->assertSee('data-testid="suppliers-duplicate-warning-message"', false);
    }

    public function test_duplicate_service_returns_empty_groups_when_no_duplicates_exist(): void
    {
        $this->signIn();

        $this->createCustomer([
            'name' => 'عميل غير مكرر',
            'phone' => '0558000001',
            'email' => 'unique-customer@example.com',
        ]);

        $this->createSupplier([
            'name' => 'مورد غير مكرر',
            'phone' => '0568000001',
            'email' => 'unique-supplier@example.com',
        ]);

        $groups = app(PartyDuplicateService::class)->allGroups();

        $this->assertCount(0, $groups['customer_phone']);
        $this->assertCount(0, $groups['customer_email']);
        $this->assertCount(0, $groups['supplier_phone']);
        $this->assertCount(0, $groups['supplier_email']);
    }

    public function test_customers_and_suppliers_index_pages_link_to_duplicate_center(): void
    {
        $this->signIn();

        $customersResponse = $this->get(route('customers.index'));
        $suppliersResponse = $this->get(route('suppliers.index'));

        $customersResponse->assertOk();
        $customersResponse->assertSee(route('party-duplicates.index'), false);
        $customersResponse->assertSee('data-testid="customers-duplicates-center-link"', false);

        $suppliersResponse->assertOk();
        $suppliersResponse->assertSee(route('party-duplicates.index'), false);
        $suppliersResponse->assertSee('data-testid="suppliers-duplicates-center-link"', false);
    }
}
