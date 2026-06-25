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

class ExpenseListFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_filter_controls_and_totals(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Branch', 'BR-FLT-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'General Filter Category',
            'slug' => 'general-filter-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Visible general expense',
            'amount' => 100,
            'tax_amount' => 15,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('المصاريف التشغيلية');
        $response->assertSee('إجمالي نتائج الفلتر');
        $response->assertSee('تصنيف المصروف');
        $response->assertSee('طريقة الدفع');
        $response->assertSee('Visible general expense');
        $response->assertSee('General Filter Category');
        $response->assertSee('نقدًا');

        $expenseTotals = $response->viewData('expenseTotals');

        $this->assertSame(1, $expenseTotals['count']);
        $this->assertSame(100.0, $expenseTotals['amount']);
        $this->assertSame(15.0, $expenseTotals['tax_amount']);
        $this->assertSame(100.0, $expenseTotals['paid_amount']);
    }

    public function test_expenses_index_filters_by_date_branch_category_and_payment_method(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();

        $mainBranch = $this->branch($companyId, 'Main Branch', 'BR-FLT-101');
        $secondaryBranch = $this->branch($companyId, 'Secondary Branch', 'BR-FLT-102');

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Rent Filter Category',
            'slug' => 'rent-list-filter-category',
        ]));

        $fuelCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Fuel Filter Category',
            'slug' => 'fuel-list-filter-category',
        ]));

        $today = now()->toDateString();
        $oldDate = now()->subDays(10)->toDateString();

        $this->expense($companyId, $mainBranch->id, $rentCategory->id, [
            'description' => 'Filtered rent cash expense',
            'amount' => 120,
            'tax_amount' => 18,
            'payment_method' => 'cash',
            'expense_date' => $today,
        ]);

        $this->expense($companyId, $secondaryBranch->id, $rentCategory->id, [
            'description' => 'Other branch rent expense',
            'amount' => 80,
            'tax_amount' => 12,
            'payment_method' => 'cash',
            'expense_date' => $today,
        ]);

        $this->expense($companyId, $mainBranch->id, $fuelCategory->id, [
            'description' => 'Fuel bank transfer expense',
            'amount' => 300,
            'tax_amount' => 45,
            'payment_method' => 'bank_transfer',
            'expense_date' => $today,
        ]);

        $this->expense($companyId, $mainBranch->id, $rentCategory->id, [
            'description' => 'Old rent cash expense',
            'amount' => 60,
            'tax_amount' => 9,
            'payment_method' => 'cash',
            'expense_date' => $oldDate,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $mainBranch->id,
            'expense_category_id' => $rentCategory->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();
        $response->assertSee('Filtered rent cash expense');
        $response->assertDontSee('Other branch rent expense');
        $response->assertDontSee('Fuel bank transfer expense');
        $response->assertDontSee('Old rent cash expense');

        $expenseTotals = $response->viewData('expenseTotals');

        $this->assertSame(1, $expenseTotals['count']);
        $this->assertSame(120.0, $expenseTotals['amount']);
        $this->assertSame(18.0, $expenseTotals['tax_amount']);
        $this->assertSame(120.0, $expenseTotals['paid_amount']);
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
            $data['code'] = 'COMP-FLT-001';
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
            'code' => 'EXP-LST-' . uniqid(),
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
