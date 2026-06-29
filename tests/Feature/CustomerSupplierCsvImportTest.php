<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerSupplierCsvImportTest extends TestCase
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

    private function uploadedCsv(string $filename, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_import_');

        file_put_contents($path, "\xEF\xBB\xBF" . $content);

        return new UploadedFile(
            $path,
            $filename,
            'text/csv',
            null,
            true
        );
    }

    private function createTestUser(?int $companyId = null, ?int $branchId = null): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Owner CSV Import Test',
            'email' => 'owner-csv-import-test@example.com',
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
            'name' => 'شركة اختبار استيراد CSV',
            'commercial_name' => 'شركة اختبار استيراد CSV',
            'email' => 'company-csv-import-test@example.com',
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
            'name' => 'فرع اختبار استيراد CSV',
            'code' => 'CSV-IMPORT',
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
            'name' => 'عميل سابق للاستيراد',
            'phone' => '0557000001',
            'email' => 'old-customer-import@example.com',
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
            'name' => 'مورد سابق للاستيراد',
            'phone' => '0558000001',
            'email' => 'old-supplier-import@example.com',
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

    public function test_customers_can_be_imported_from_csv(): void
    {
        $this->signIn();

        $csv = implode("\n", [
            'اسم العميل,الهاتف,البريد الإلكتروني,المدينة,الحالة',
            'عميل مستورد,0557000010,imported-customer@example.com,الرياض,نشط',
        ]);

        $response = $this->post(route('customers.import'), [
            'csv_file' => $this->uploadedCsv('customers.csv', $csv),
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'name' => 'عميل مستورد',
            'phone' => '0557000010',
            'email' => 'imported-customer@example.com',
        ]);
    }

    public function test_existing_customer_is_updated_by_company_and_phone(): void
    {
        $this->signIn();

        $customer = $this->createCustomer([
            'name' => 'عميل قديم',
            'phone' => '0557000020',
            'email' => 'old@example.com',
        ]);

        $csv = implode("\n", [
            'اسم العميل,الهاتف,البريد الإلكتروني,المدينة,الحالة',
            'عميل محدث,0557000020,updated@example.com,جدة,نشط',
        ]);

        $response = $this->post(route('customers.import'), [
            'csv_file' => $this->uploadedCsv('customers.csv', $csv),
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'عميل محدث',
            'phone' => '0557000020',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_customer_import_skips_rows_without_name(): void
    {
        $this->signIn();

        $csv = implode("\n", [
            'اسم العميل,الهاتف,البريد الإلكتروني,المدينة,الحالة',
            ',0557000030,missing-name@example.com,الرياض,نشط',
        ]);

        $response = $this->post(route('customers.import'), [
            'csv_file' => $this->uploadedCsv('customers.csv', $csv),
        ]);

        $response->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('customers', [
            'phone' => '0557000030',
        ]);
    }

    public function test_suppliers_can_be_imported_from_csv(): void
    {
        $this->signIn();

        $csv = implode("\n", [
            'اسم المورد,مسؤول التواصل,الهاتف,البريد الإلكتروني,المدينة,الحالة',
            'مورد مستورد,مسؤول جديد,0558000010,imported-supplier@example.com,الرياض,نشط',
        ]);

        $response = $this->post(route('suppliers.import'), [
            'csv_file' => $this->uploadedCsv('suppliers.csv', $csv),
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'name' => 'مورد مستورد',
            'phone' => '0558000010',
            'email' => 'imported-supplier@example.com',
        ]);
    }

    public function test_existing_supplier_is_updated_by_company_and_phone(): void
    {
        $this->signIn();

        $supplier = $this->createSupplier([
            'name' => 'مورد قديم',
            'phone' => '0558000020',
            'email' => 'old-supplier@example.com',
        ]);

        $csv = implode("\n", [
            'اسم المورد,مسؤول التواصل,الهاتف,البريد الإلكتروني,المدينة,الحالة',
            'مورد محدث,مسؤول محدث,0558000020,updated-supplier@example.com,جدة,نشط',
        ]);

        $response = $this->post(route('suppliers.import'), [
            'csv_file' => $this->uploadedCsv('suppliers.csv', $csv),
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'مورد محدث',
            'phone' => '0558000020',
            'email' => 'updated-supplier@example.com',
        ]);
    }

    public function test_supplier_import_skips_rows_without_name(): void
    {
        $this->signIn();

        $csv = implode("\n", [
            'اسم المورد,مسؤول التواصل,الهاتف,البريد الإلكتروني,المدينة,الحالة',
            ',مسؤول ناقص,0558000030,missing-supplier-name@example.com,الرياض,نشط',
        ]);

        $response = $this->post(route('suppliers.import'), [
            'csv_file' => $this->uploadedCsv('suppliers.csv', $csv),
        ]);

        $response->assertRedirect(route('suppliers.index'));

        $this->assertDatabaseMissing('suppliers', [
            'phone' => '0558000030',
        ]);
    }

    public function test_customer_and_supplier_indexes_have_import_forms(): void
    {
        $this->signIn();

        $customerResponse = $this->get(route('customers.index'));
        $supplierResponse = $this->get(route('suppliers.index'));

        $customerResponse->assertOk();
        $customerResponse->assertSee(route('customers.import'), false);
        $customerResponse->assertSee('data-testid="customers-import-form"', false);

        $supplierResponse->assertOk();
        $supplierResponse->assertSee(route('suppliers.import'), false);
        $supplierResponse->assertSee('data-testid="suppliers-import-form"', false);
    }
}
