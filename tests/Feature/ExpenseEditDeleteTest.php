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

class ExpenseEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_expense_edit_page(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Edit Branch', 'BR-EDT-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Editable Expense Category',
            'slug' => 'editable-expense-category',
        ]));

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense before editing',
            'amount' => 100,
            'tax_amount' => 15,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.edit', $expense));

        $response->assertOk();
        $response->assertSee('تعديل مصروف تشغيلي');
        $response->assertSee('Expense before editing');
        $response->assertSee('Editable Expense Category');
    }

    public function test_owner_can_update_expense(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Update Branch', 'BR-EDT-101');

        $oldCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Old Expense Category',
            'slug' => 'old-expense-category',
        ]));

        $newCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'New Expense Category',
            'slug' => 'new-expense-category',
        ]));

        $expense = $this->expense($companyId, $branch->id, $oldCategory->id, [
            'description' => 'Old expense description',
            'amount' => 100,
            'tax_amount' => 15,
            'payment_method' => 'cash',
        ]);

        $this->actingAs($user)
            ->from(route('expenses.edit', $expense))
            ->patch(route('expenses.update', $expense), [
                'branch_id' => $branch->id,
                'expense_category_id' => $newCategory->id,
                'description' => 'Updated expense description',
                'amount' => 250,
                'tax_amount' => 37.5,
                'payment_method' => 'bank_transfer',
                'expense_date' => now()->toDateString(),
                'reference_number' => 'REF-UPDATED-001',
                'notes' => 'Updated notes',
            ])
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $newCategory->id,
            'description' => 'Updated expense description',
            'amount' => 250,
            'tax_amount' => 37.5,
            'payment_method' => 'bank_transfer',
            'reference_number' => 'REF-UPDATED-001',
            'notes' => 'Updated notes',
            'is_paid' => true,
        ]);
    }

    public function test_expense_update_rejects_inactive_category(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Reject Branch', 'BR-EDT-201');

        $activeCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Active Expense Category',
            'slug' => 'active-expense-category-edit',
            'is_active' => true,
        ]));

        $inactiveCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Inactive Expense Category',
            'slug' => 'inactive-expense-category-edit',
            'is_active' => false,
        ]));

        $expense = $this->expense($companyId, $branch->id, $activeCategory->id, [
            'description' => 'Expense active category',
            'amount' => 100,
        ]);

        $this->actingAs($user)
            ->from(route('expenses.edit', $expense))
            ->patch(route('expenses.update', $expense), [
                'branch_id' => $branch->id,
                'expense_category_id' => $inactiveCategory->id,
                'description' => 'Should not update',
                'amount' => 500,
                'tax_amount' => 75,
                'payment_method' => 'cash',
                'expense_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('expense_category_id');

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'expense_category_id' => $activeCategory->id,
            'description' => 'Expense active category',
        ]);
    }

    public function test_owner_can_delete_expense(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Delete Branch', 'BR-EDT-301');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Delete Expense Category',
            'slug' => 'delete-expense-category',
        ]));

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense to delete',
        ]);

        $this->actingAs($user)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }

    private function companyId(): int
    {
        $existingCompanyId = DB::table('companies')->value('id');

        if ($existingCompanyId) {
            return (int) $existingCompanyId;
        }

        $columns = Schema::getColumnListing('companies');

        $data = [];

        if (in_array('name', $columns, true)) {
            $data['name'] = 'Test Company';
        }

        if (in_array('name_ar', $columns, true)) {
            $data['name_ar'] = 'شركة اختبار';
        }

        if (in_array('name_en', $columns, true)) {
            $data['name_en'] = 'Test Company';
        }

        if (in_array('legal_name', $columns, true)) {
            $data['legal_name'] = 'Test Company Ltd';
        }

        if (in_array('legal_name_ar', $columns, true)) {
            $data['legal_name_ar'] = 'شركة اختبار المحدودة';
        }

        if (in_array('legal_name_en', $columns, true)) {
            $data['legal_name_en'] = 'Test Company Ltd';
        }

        if (in_array('slug', $columns, true)) {
            $data['slug'] = 'test-company';
        }

        if (in_array('code', $columns, true)) {
            $data['code'] = 'COMP-EDT-001';
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
        $existingBranch = Branch::query()
            ->where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if ($existingBranch) {
            return $existingBranch;
        }

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
            'code' => 'EXP-EDT-' . uniqid(),
            'description' => 'Test expense',
            'amount' => 100,
            'tax_amount' => 15,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
            'reference_number' => null,
            'notes' => null,
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
