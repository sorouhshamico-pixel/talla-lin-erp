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

class ExpensePaymentStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_unpaid_expense(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Payment Branch', 'BR-PAY-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Payment Status Category',
            'slug' => 'payment-status-category',
        ]));

        $this->actingAs($user)
            ->post(route('expenses.store'), [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'Unpaid expense from form',
                'amount' => 450,
                'tax_amount' => 67.5,
                'payment_method' => 'bank_transfer',
                'is_paid' => '0',
                'expense_date' => now()->toDateString(),
                'reference_number' => 'UNPAID-001',
                'notes' => 'Created as unpaid',
            ])
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::query()
            ->where('description', 'Unpaid expense from form')
            ->firstOrFail();

        $this->assertFalse($expense->is_paid);
        $this->assertSame('bank_transfer', $expense->payment_method);
        $this->assertSame('UNPAID-001', $expense->reference_number);
    }

    public function test_owner_can_update_expense_payment_status(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Payment Update Branch', 'BR-PAY-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Payment Update Category',
            'slug' => 'payment-update-category',
        ]));

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Paid expense before update',
            'amount' => 200,
            'tax_amount' => 30,
            'is_paid' => true,
        ]);

        $this->actingAs($user)
            ->patch(route('expenses.update', $expense), [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'Unpaid expense after update',
                'amount' => 200,
                'tax_amount' => 30,
                'payment_method' => 'cash',
                'is_paid' => '0',
                'expense_date' => now()->toDateString(),
                'reference_number' => 'PAY-UPD-001',
                'notes' => 'Changed to unpaid',
            ])
            ->assertRedirect(route('expenses.index'));

        $expense->refresh();

        $this->assertFalse($expense->is_paid);
        $this->assertSame('Unpaid expense after update', $expense->description);
    }

    public function test_expenses_index_filters_by_payment_status_and_shows_paid_unpaid_totals(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Payment Filter Branch', 'BR-PAY-201');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Payment Filter Category',
            'slug' => 'payment-filter-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Visible unpaid expense',
            'amount' => 300,
            'tax_amount' => 45,
            'is_paid' => false,
        ]);

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Hidden paid expense',
            'amount' => 150,
            'tax_amount' => 22.5,
            'is_paid' => true,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();
        $response->assertSee('حالة الدفع');
        $response->assertSee('إجمالي المصاريف غير المدفوعة');
        $response->assertSee('Visible unpaid expense');
        $response->assertDontSee('Hidden paid expense');

        $expenseTotals = $response->viewData('expenseTotals');

        $this->assertSame(1, $expenseTotals['count']);
        $this->assertSame(300.0, $expenseTotals['amount']);
        $this->assertSame(0.0, $expenseTotals['paid_amount']);
        $this->assertSame(300.0, $expenseTotals['unpaid_amount']);
    }

    public function test_reports_include_unpaid_expense_totals(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Report Payment Branch', 'BR-PAY-301');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Report Payment Category',
            'slug' => 'report-payment-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Report paid expense',
            'amount' => 100,
            'tax_amount' => 15,
            'is_paid' => true,
        ]);

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Report unpaid expense',
            'amount' => 250,
            'tax_amount' => 37.5,
            'is_paid' => false,
        ]);

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('المصاريف غير المدفوعة');

        $expenses = $response->viewData('expenses');

        $this->assertSame(2, $expenses['count']);
        $this->assertSame(350.0, $expenses['amount']);
        $this->assertSame(100.0, $expenses['paid_amount']);
        $this->assertSame(250.0, $expenses['unpaid_amount']);
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
            $data['code'] = 'COMP-PAY-001';
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
            'code' => 'EXP-PAY-' . uniqid(),
            'description' => 'Test payment status expense',
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
