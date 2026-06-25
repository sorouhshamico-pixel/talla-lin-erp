<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseCodeSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_code_is_generated_from_highest_existing_code_not_count(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-CODE-001');
        $branch = $this->branch($companyId, 'Main Code Branch', 'BR-CODE-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Code Category',
            'slug' => 'code-category',
        ]));

        $firstExpense = $this->expense($companyId, $branch->id, $category->id, [
            'code' => 'EXP-000001',
            'description' => 'First existing expense',
        ]);

        $this->expense($companyId, $branch->id, $category->id, [
            'code' => 'EXP-000002',
            'description' => 'Second existing expense',
        ]);

        $firstExpense->delete();

        $this->actingAs($user)
            ->post(route('expenses.store'), [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'Generated after deleted old expense',
                'amount' => 300,
                'tax_amount' => 45,
                'payment_method' => 'cash',
                'is_paid' => '1',
                'expense_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'description' => 'Generated after deleted old expense',
            'code' => 'EXP-000003',
        ]);
    }

    public function test_expense_code_sequence_is_independent_per_company(): void
    {
        $user = User::factory()->create();

        $firstCompanyId = $this->companyId('COMP-CODE-101');
        $secondCompanyId = $this->companyId('COMP-CODE-102');

        $firstBranch = $this->branch($firstCompanyId, 'First Company Branch', 'BR-CODE-101');
        $secondBranch = $this->branch($secondCompanyId, 'Second Company Branch', 'BR-CODE-102');

        $firstCategory = ExpenseCategory::query()->create($this->expenseCategoryData($firstCompanyId, [
            'name' => 'First Company Code Category',
            'slug' => 'first-company-code-category',
        ]));

        $secondCategory = ExpenseCategory::query()->create($this->expenseCategoryData($secondCompanyId, [
            'name' => 'Second Company Code Category',
            'slug' => 'second-company-code-category',
        ]));

        $this->expense($firstCompanyId, $firstBranch->id, $firstCategory->id, [
            'code' => 'EXP-000001',
            'description' => 'First company expense 1',
        ]);

        $this->expense($firstCompanyId, $firstBranch->id, $firstCategory->id, [
            'code' => 'EXP-000002',
            'description' => 'First company expense 2',
        ]);

        $this->expense($secondCompanyId, $secondBranch->id, $secondCategory->id, [
            'code' => 'EXP-000001',
            'description' => 'Second company expense 1',
        ]);

        $this->actingAs($user)
            ->post(route('expenses.store'), [
                'branch_id' => $secondBranch->id,
                'expense_category_id' => $secondCategory->id,
                'description' => 'Second company generated expense',
                'amount' => 180,
                'tax_amount' => 27,
                'payment_method' => 'bank_transfer',
                'is_paid' => '1',
                'expense_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'company_id' => $secondCompanyId,
            'description' => 'Second company generated expense',
            'code' => 'EXP-000002',
        ]);

        $this->assertDatabaseMissing('expenses', [
            'company_id' => $secondCompanyId,
            'description' => 'Second company generated expense',
            'code' => 'EXP-000003',
        ]);
    }

    public function test_expense_code_must_be_unique_within_same_company(): void
    {
        $companyId = $this->companyId('COMP-CODE-201');
        $branch = $this->branch($companyId, 'Unique Code Branch', 'BR-CODE-201');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Unique Code Category',
            'slug' => 'unique-code-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'code' => 'EXP-000001',
            'description' => 'Unique code original',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        $this->expense($companyId, $branch->id, $category->id, [
            'code' => 'EXP-000001',
            'description' => 'Unique code duplicate',
        ]);
    }

    private function companyId(string $code): int
    {
        $columns = Schema::getColumnListing('companies');

        $data = [];

        if (in_array('name', $columns, true)) {
            $data['name'] = 'Test Company ' . $code;
        }

        if (in_array('name_ar', $columns, true)) {
            $data['name_ar'] = 'شركة اختبار ' . $code;
        }

        if (in_array('name_en', $columns, true)) {
            $data['name_en'] = 'Test Company ' . $code;
        }

        if (in_array('legal_name', $columns, true)) {
            $data['legal_name'] = 'Test Company Ltd ' . $code;
        }

        if (in_array('legal_name_ar', $columns, true)) {
            $data['legal_name_ar'] = 'شركة اختبار المحدودة ' . $code;
        }

        if (in_array('legal_name_en', $columns, true)) {
            $data['legal_name_en'] = 'Test Company Ltd ' . $code;
        }

        if (in_array('slug', $columns, true)) {
            $data['slug'] = strtolower($code);
        }

        if (in_array('code', $columns, true)) {
            $data['code'] = $code;
        }

        if (in_array('is_active', $columns, true)) {
            $data['is_active'] = true;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        return DB::table('companies')->insertGetId($this->fillSqliteRequiredColumns('companies', $data));
    }

    private function branch(int $companyId, string $name, string $code): Branch
    {
        $columns = Schema::getColumnListing('branches');

        $data = [];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('name', $columns, true)) {
            $data['name'] = $name;
        }

        if (in_array('name_ar', $columns, true)) {
            $data['name_ar'] = $name;
        }

        if (in_array('name_en', $columns, true)) {
            $data['name_en'] = $name;
        }

        if (in_array('slug', $columns, true)) {
            $data['slug'] = strtolower(str_replace(' ', '-', $code));
        }

        if (in_array('code', $columns, true)) {
            $data['code'] = $code;
        }

        if (in_array('is_main', $columns, true)) {
            $data['is_main'] = false;
        }

        if (in_array('is_active', $columns, true)) {
            $data['is_active'] = true;
        }

        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = now();
        }

        $id = DB::table('branches')->insertGetId($this->fillSqliteRequiredColumns('branches', $data));

        return Branch::query()->findOrFail($id);
    }

    private function expenseCategoryData(int $companyId, array $overrides = []): array
    {
        $data = [
            'name' => 'Expense Category',
            'slug' => 'expense-category-' . uniqid(),
            'description' => 'Expense category description',
            'is_active' => true,
        ];

        if (Schema::hasColumn('expense_categories', 'company_id')) {
            $data['company_id'] = $companyId;
        }

        return array_merge($data, $overrides);
    }

    private function expense(int $companyId, int $branchId, int $categoryId, array $overrides = []): Expense
    {
        $data = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'expense_category_id' => $categoryId,
            'user_id' => null,
            'code' => 'EXP-CODE-' . uniqid(),
            'description' => 'Test code sequence expense',
            'amount' => 100,
            'tax_amount' => 15,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
            'reference_number' => null,
            'notes' => null,
            'attachment_path' => null,
            'attachment_original_name' => null,
            'is_paid' => true,
        ];

        return Expense::query()->create(array_merge($data, $overrides));
    }

    private function fillSqliteRequiredColumns(string $table, array $data): array
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return $data;
        }

        $columns = DB::select("PRAGMA table_info({$table})");

        foreach ($columns as $column) {
            $name = $column->name;

            if ($name === 'id') {
                continue;
            }

            if (array_key_exists($name, $data)) {
                continue;
            }

            $isRequired = (int) $column->notnull === 1 && $column->dflt_value === null;

            if (! $isRequired) {
                continue;
            }

            $data[$name] = $this->defaultValueForColumn($name, (string) $column->type);
        }

        return $data;
    }

    private function defaultValueForColumn(string $name, string $type): mixed
    {
        $lowerName = strtolower($name);
        $lowerType = strtolower($type);

        if (str_contains($lowerName, 'email')) {
            return $lowerName . '@example.test';
        }

        if (str_contains($lowerName, 'phone') || str_contains($lowerName, 'mobile')) {
            return '0500000000';
        }

        if (str_contains($lowerName, 'date')) {
            return now()->toDateString();
        }

        if (str_contains($lowerName, 'time')) {
            return now()->toDateTimeString();
        }

        if (
            str_contains($lowerType, 'int')
            || str_contains($lowerType, 'decimal')
            || str_contains($lowerType, 'double')
            || str_contains($lowerType, 'float')
            || str_contains($lowerType, 'real')
        ) {
            return 1;
        }

        if (
            str_contains($lowerType, 'bool')
            || str_starts_with($lowerName, 'is_')
            || str_starts_with($lowerName, 'has_')
        ) {
            return true;
        }

        return 'test-' . str_replace('_', '-', $lowerName);
    }
}
