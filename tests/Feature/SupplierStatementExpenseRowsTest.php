<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupplierStatementExpenseRowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_statement_page_displays_supplier_expense_rows_and_balance(): void
    {
        $this->assertTrue(Schema::hasColumn('expenses', 'supplier_id'));

        $companyId = $this->createCompanyId();
        $branch = $this->createBranch($companyId);
        $category = $this->createExpenseCategory($companyId);
        $supplier = $this->createSupplier($companyId, $branch->id);
        $user = $this->createUser($companyId, $branch->id);

        $this->insertExpense($companyId, $branch->id, $category->id, $supplier->id, [
            'code' => 'EXP-SUP-STMT-001',
            'description' => 'مصروف مورد لاختبار كشف الحساب',
            'amount' => 1250,
            'tax_amount' => 0,
            'is_paid' => false,
            'payment_method' => 'bank_transfer',
            'expense_date' => '2026-07-05',
            'reference_number' => 'SUP-EXP-REF-001',
        ]);

        $response = $this->actingAs($user)
            ->get(route('suppliers.statement', $supplier));

        $response->assertOk();

        $response->assertSee('كشف حساب المورد');
        $response->assertSee('مصروف مورد لاختبار كشف الحساب');
        $response->assertSee('مصروف');
        $response->assertSee('unpaid');
        $response->assertSee('1,250.00');
        $response->assertSee('data-testid="statement-table"', false);
        $response->assertDontSee('data-testid="statement-no-source"', false);
    }

    private function createUser(?int $companyId, ?int $branchId): User
    {
        $columns = Schema::getColumnListing('users');

        $data = [
            'name' => 'Supplier Statement Rows User',
            'email' => 'supplier-statement-rows@example.com',
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
            'name' => 'شركة اختبار صفوف كشف حساب المورد',
            'commercial_name' => 'شركة اختبار صفوف كشف حساب المورد',
            'email' => 'supplier-statement-rows-company@example.com',
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

    private function createBranch(?int $companyId): Branch
    {
        $columns = Schema::getColumnListing('branches');

        $data = [
            'name' => 'فرع اختبار صفوف كشف حساب المورد',
            'code' => 'SUP-STMT-ROWS',
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

        return Branch::unguarded(fn () => Branch::query()->create($data));
    }

    private function createExpenseCategory(?int $companyId): ExpenseCategory
    {
        $columns = Schema::getColumnListing('expense_categories');

        $data = [
            'name' => 'تصنيف كشف حساب المورد',
            'slug' => 'supplier-statement-rows-category',
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

        $data = $this->fillRequiredColumns('expense_categories', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return ExpenseCategory::unguarded(fn () => ExpenseCategory::query()->create($data));
    }

    private function createSupplier(?int $companyId, ?int $branchId): Supplier
    {
        $columns = Schema::getColumnListing('suppliers');

        $data = [
            'name' => 'مورد صفوف كشف الحساب',
            'phone' => '0569800088',
            'email' => 'supplier-statement-rows@example.com',
            'city' => 'الرياض',
            'is_active' => true,
        ];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('branch_id', $columns, true)) {
            $data['branch_id'] = $branchId;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $data = $this->fillRequiredColumns('suppliers', $data);
        $data = array_intersect_key($data, array_flip($columns));

        return Supplier::unguarded(fn () => Supplier::query()->create($data));
    }

    private function insertExpense(?int $companyId, int $branchId, int $categoryId, int $supplierId, array $overrides): void
    {
        $columns = Schema::getColumnListing('expenses');

        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'expense_category_id' => $categoryId,
            'supplier_id' => $supplierId,
            'user_id' => DB::table('users')->value('id'),
            'code' => $overrides['code'],
            'description' => $overrides['description'],
            'amount' => $overrides['amount'],
            'tax_amount' => $overrides['tax_amount'],
            'payment_method' => $overrides['payment_method'],
            'expense_date' => $overrides['expense_date'],
            'reference_number' => $overrides['reference_number'],
            'notes' => null,
            'is_paid' => $overrides['is_paid'],
            'attachment_path' => null,
            'attachment_original_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $data = $this->fillRequiredColumns('expenses', $data);
        $data = array_intersect_key($data, array_flip($columns));

        DB::table('expenses')->insert($data);
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
                str_contains($columnName, 'branch_id') => 1,
                str_contains($columnName, 'expense_category_id') => 1,
                str_contains($columnName, 'supplier_id') => 1,
                str_contains($columnName, 'user_id') => 1,
                str_contains($columnName, 'email') => $table . '-required@example.com',
                str_contains($columnName, 'phone') => '0500000000',
                str_contains($columnName, 'active') => true,
                str_contains($columnName, 'paid') => false,
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
}
