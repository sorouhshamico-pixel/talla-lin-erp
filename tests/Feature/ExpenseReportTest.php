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

class ExpenseReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_include_expense_category_and_payment_breakdowns(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId);

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Rent Expenses',
            'slug' => 'rent-expenses',
        ]));

        $deliveryCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Delivery Expenses',
            'slug' => 'delivery-expenses',
        ]));

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'amount' => 100,
            'tax_amount' => 15,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'amount' => 50,
            'tax_amount' => 7.5,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $deliveryCategory->id, [
            'amount' => 25,
            'tax_amount' => 3.75,
            'payment_method' => 'bank_transfer',
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('تفصيل المصاريف حسب التصنيف');
        $response->assertSee('تفصيل المصاريف حسب طريقة الدفع');
        $response->assertSee('Rent Expenses');
        $response->assertSee('Delivery Expenses');
        $response->assertSee('نقدًا');
        $response->assertSee('تحويل بنكي');

        $expenses = $response->viewData('expenses');
        $profit = $response->viewData('profit');
        $categoryBreakdown = collect($response->viewData('expenseCategoryBreakdown'));
        $paymentBreakdown = collect($response->viewData('expensePaymentBreakdown'));

        $this->assertSame(3, $expenses['count']);
        $this->assertSame(175.0, $expenses['amount']);
        $this->assertSame(26.25, $expenses['tax_amount']);
        $this->assertSame(175.0, $profit['operating_expenses_total']);

        $rentRow = $categoryBreakdown->firstWhere('slug', 'rent-expenses');
        $this->assertNotNull($rentRow);
        $this->assertSame(2, $rentRow['expenses_count']);
        $this->assertSame(150.0, $rentRow['total_amount']);

        $cashRow = $paymentBreakdown->firstWhere('payment_method', 'cash');
        $this->assertNotNull($cashRow);
        $this->assertSame(2, $cashRow['expenses_count']);
        $this->assertSame(150.0, $cashRow['total_amount']);
    }

    public function test_reports_expense_filters_by_category_and_payment_method_affect_net_profit(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId);

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Rent Filter Category',
            'slug' => 'rent-filter-category',
        ]));

        $fuelCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Fuel Filter Category',
            'slug' => 'fuel-filter-category',
        ]));

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'amount' => 120,
            'tax_amount' => 18,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'amount' => 80,
            'tax_amount' => 12,
            'payment_method' => 'bank_transfer',
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $fuelCategory->id, [
            'amount' => 300,
            'tax_amount' => 45,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('reports.index', [
            'expense_category_id' => $rentCategory->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $expenses = $response->viewData('expenses');
        $profit = $response->viewData('profit');
        $categoryBreakdown = collect($response->viewData('expenseCategoryBreakdown'));
        $paymentBreakdown = collect($response->viewData('expensePaymentBreakdown'));

        $this->assertSame(1, $expenses['count']);
        $this->assertSame(120.0, $expenses['amount']);
        $this->assertSame(18.0, $expenses['tax_amount']);
        $this->assertSame(120.0, $profit['operating_expenses_total']);
        $this->assertSame(-120.0, $profit['net_profit_after_expenses']);

        $this->assertCount(1, $categoryBreakdown);
        $this->assertSame('rent-filter-category', $categoryBreakdown->first()['slug']);

        $this->assertCount(1, $paymentBreakdown);
        $this->assertSame('cash', $paymentBreakdown->first()['payment_method']);
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
            $data['code'] = 'COMP-001';
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

    private function branch(int $companyId): Branch
    {
        $branch = Branch::query()->where('company_id', $companyId)->first();

        if ($branch) {
            return $branch;
        }

        $columns = Schema::getColumnListing('branches');

        $data = [];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $companyId;
        }

        if (in_array('name', $columns, true)) {
            $data['name'] = 'Main Branch';
        }

        if (in_array('name_ar', $columns, true)) {
            $data['name_ar'] = 'الفرع الرئيسي';
        }

        if (in_array('name_en', $columns, true)) {
            $data['name_en'] = 'Main Branch';
        }

        if (in_array('slug', $columns, true)) {
            $data['slug'] = 'main-branch';
        }

        if (in_array('code', $columns, true)) {
            $data['code'] = 'BR-001';
        }

        if (in_array('is_main', $columns, true)) {
            $data['is_main'] = true;
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
            'code' => 'EXP-TST-' . uniqid(),
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
