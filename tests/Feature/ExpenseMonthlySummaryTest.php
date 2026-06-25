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

class ExpenseMonthlySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_current_month_summary(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-MONTH-001');

        $branch = $this->branch($companyId, 'Monthly Summary Branch', 'BR-MONTH-001');
        $otherBranch = $this->branch($companyId, 'Other Monthly Branch', 'BR-MONTH-002');

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Monthly Rent Category',
            'slug' => 'monthly-rent-category',
        ]));

        $fuelCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Monthly Fuel Category',
            'slug' => 'monthly-fuel-category',
        ]));

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'description' => 'Current month paid rent',
            'amount' => 500,
            'tax_amount' => 75,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'description' => 'Current month unpaid rent',
            'amount' => 300,
            'tax_amount' => 45,
            'payment_method' => 'bank_transfer',
            'is_paid' => false,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $fuelCategory->id, [
            'description' => 'Current month paid fuel',
            'amount' => 150,
            'tax_amount' => 22.5,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'description' => 'Old month rent should not affect monthly summary',
            'amount' => 900,
            'tax_amount' => 135,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->subMonth()->toDateString(),
        ]);

        $this->expense($companyId, $otherBranch->id, $rentCategory->id, [
            'description' => 'Other branch should not affect filtered monthly summary',
            'amount' => 700,
            'tax_amount' => 105,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
        ]));

        $response->assertOk();

        $monthlySummaryHtml = $this->monthlySummaryHtml($response->getContent());

        $this->assertStringContainsString('ملخص مصاريف الشهر الحالي', $monthlySummaryHtml);
        $this->assertStringContainsString('إجمالي مصاريف الشهر الحالي', $monthlySummaryHtml);
        $this->assertStringContainsString('950.00 ريال', $monthlySummaryHtml);
        $this->assertStringContainsString('إجمالي المدفوع خلال الشهر', $monthlySummaryHtml);
        $this->assertStringContainsString('650.00 ريال', $monthlySummaryHtml);
        $this->assertStringContainsString('إجمالي غير المدفوع خلال الشهر', $monthlySummaryHtml);
        $this->assertStringContainsString('300.00 ريال', $monthlySummaryHtml);
        $this->assertStringContainsString('أعلى تصنيف مصروف خلال الشهر', $monthlySummaryHtml);
        $this->assertStringContainsString('Monthly Rent Category', $monthlySummaryHtml);
        $this->assertStringContainsString('800.00 ريال', $monthlySummaryHtml);

        $this->assertStringNotContainsString('900.00 ريال', $monthlySummaryHtml);
        $this->assertStringNotContainsString('700.00 ريال', $monthlySummaryHtml);
    }

    public function test_monthly_summary_respects_payment_status_filter(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-MONTH-101');

        $branch = $this->branch($companyId, 'Monthly Payment Branch', 'BR-MONTH-101');

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Monthly Payment Rent Category',
            'slug' => 'monthly-payment-rent-category',
        ]));

        $fuelCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Monthly Payment Fuel Category',
            'slug' => 'monthly-payment-fuel-category',
        ]));

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'description' => 'Monthly unpaid rent',
            'amount' => 400,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $fuelCategory->id, [
            'description' => 'Monthly paid fuel',
            'amount' => 250,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();

        $monthlySummaryHtml = $this->monthlySummaryHtml($response->getContent());

        $this->assertStringContainsString('ملخص مصاريف الشهر الحالي', $monthlySummaryHtml);
        $this->assertStringContainsString('400.00 ريال', $monthlySummaryHtml);
        $this->assertStringContainsString('Monthly Payment Rent Category', $monthlySummaryHtml);

        $this->assertStringNotContainsString('250.00 ريال', $monthlySummaryHtml);
        $this->assertStringNotContainsString('Monthly Payment Fuel Category', $monthlySummaryHtml);
    }

    private function monthlySummaryHtml(string $content): string
    {
        $startNeedle = '<h2 style="margin-top:0;">ملخص مصاريف الشهر الحالي</h2>';
        $endNeedle = '<div class="grid" style="margin-bottom:20px;">';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Monthly summary section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Monthly summary section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition);
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
            'code' => 'EXP-MONTH-' . uniqid(),
            'description' => 'Test monthly expense',
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
