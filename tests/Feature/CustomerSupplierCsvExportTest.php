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

class CustomerSupplierCsvExportTest extends TestCase
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
            'name' => 'Owner CSV Export Test',
            'email' => 'owner-csv-export-test@example.com',
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
            'name' => 'شركة اختبار تصدير العملاء والموردين',
            'commercial_name' => 'شركة اختبار تصدير العملاء والموردين',
            'email' => 'company-csv-export-test@example.com',
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
            'name' => 'فرع اختبار تصدير العملاء والموردين',
            'code' => 'CSV-EXPORT',
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
            'name' => 'عميل تصدير CSV',
            'phone' => '0551111111',
            'email' => 'customer-csv-export@example.com',
            'vat_number' => '300000000000010',
            'tax_number' => '300000000000010',
            'commercial_registration' => '1010000010',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'عميل خاص باختبار تصدير CSV',
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
            'name' => 'مورد تصدير CSV',
            'contact_name' => 'مسؤول المورد',
            'contact_person' => 'مسؤول المورد',
            'phone' => '0552222222',
            'email' => 'supplier-csv-export@example.com',
            'vat_number' => '300000000000020',
            'tax_number' => '300000000000020',
            'commercial_registration' => '1010000020',
            'address' => 'الرياض',
            'city' => 'الرياض',
            'notes' => 'مورد خاص باختبار تصدير CSV',
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

    public function test_customers_can_be_exported_as_csv(): void
    {
        $this->signIn();

        $customer = $this->createCustomer();

        $response = $this->get(route('customers.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $contentDisposition = $response->headers->get('Content-Disposition');

        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('customers-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $response->assertSee('اسم العميل');
        $response->assertSee($customer->name);
        $response->assertSee($customer->phone);
    }

    public function test_suppliers_can_be_exported_as_csv(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier();

        $response = $this->get(route('suppliers.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $contentDisposition = $response->headers->get('Content-Disposition');

        $this->assertNotNull($contentDisposition);
        $this->assertStringContainsString('suppliers-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $response->assertSee('اسم المورد');
        $response->assertSee($supplier->name);
        $response->assertSee($supplier->phone);
    }

    public function test_customers_index_has_export_link(): void
    {
        $this->signIn();

        $response = $this->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee(route('customers.export'), false);
    }

    public function test_suppliers_index_has_export_link(): void
    {
        $this->signIn();

        $response = $this->get(route('suppliers.index'));

        $response->assertOk();
        $response->assertSee(route('suppliers.export'), false);
    }
}
