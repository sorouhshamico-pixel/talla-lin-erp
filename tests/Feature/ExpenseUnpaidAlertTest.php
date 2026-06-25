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

class ExpenseUnpaidAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_unpaid_expense_alert_respecting_current_filters(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-UNPAID-001');

        $branch = $this->branch($companyId, 'Unpaid Alert Branch', 'BR-UNPAID-001');
        $otherBranch = $this->branch($companyId, 'Other Unpaid Alert Branch', 'BR-UNPAID-002');

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Unpaid Rent Category',
            'slug' => 'unpaid-rent-category',
        ]));

        $fuelCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Unpaid Fuel Category',
            'slug' => 'unpaid-fuel-category',
        ]));

        $oldestDate = now()->subDays(8)->toDateString();

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'description' => 'Oldest unpaid rent expense',
            'amount' => 400,
            'tax_amount' => 60,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => $oldestDate,
        ]);

        $this->expense($companyId, $branch->id, $fuelCategory->id, [
            'description' => 'Recent unpaid fuel expense',
            'amount' => 250,
            'tax_amount' => 37.5,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $rentCategory->id, [
            'description' => 'Paid expense should not affect unpaid alert',
            'amount' => 900,
            'tax_amount' => 135,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $otherBranch->id, $rentCategory->id, [
            'description' => 'Other branch unpaid should not affect filtered unpaid alert',
            'amount' => 800,
            'tax_amount' => 120,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $alertHtml = $this->unpaidAlertHtml($response->getContent());

        $this->assertStringContainsString('تنبيه المصاريف غير المدفوعة', $alertHtml);
        $this->assertStringContainsString('عدد المصاريف غير المدفوعة', $alertHtml);
        $this->assertStringContainsString('>2<', $this->withoutWhitespace($alertHtml));
        $this->assertStringContainsString('إجمالي قيمة المصاريف غير المدفوعة', $alertHtml);
        $this->assertStringContainsString('650.00 ريال', $alertHtml);
        $this->assertStringContainsString('تاريخ أقدم مصروف غير مدفوع', $alertHtml);
        $this->assertStringContainsString($oldestDate, $alertHtml);
        $this->assertStringContainsString('أقدم مصروف غير مدفوع', $alertHtml);
        $this->assertStringContainsString('Oldest unpaid rent expense', $alertHtml);
        $this->assertStringContainsString('Unpaid Rent Category', $alertHtml);
        $this->assertStringContainsString('400.00 ريال', $alertHtml);

        $this->assertStringNotContainsString('900.00 ريال', $alertHtml);
        $this->assertStringNotContainsString('800.00 ريال', $alertHtml);
        $this->assertStringNotContainsString('Paid expense should not affect unpaid alert', $alertHtml);
        $this->assertStringNotContainsString('Other branch unpaid should not affect filtered unpaid alert', $alertHtml);
    }

    public function test_unpaid_expense_alert_respects_paid_filter(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-UNPAID-101');

        $branch = $this->branch($companyId, 'Paid Filter Alert Branch', 'BR-UNPAID-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Paid Filter Category',
            'slug' => 'paid-filter-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Unpaid hidden by paid filter',
            'amount' => 500,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => now()->toDateString(),
        ]);

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Paid visible in list but not unpaid alert',
            'amount' => 300,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_status' => 'paid',
        ]));

        $response->assertOk();

        $alertHtml = $this->unpaidAlertHtml($response->getContent());

        $this->assertStringContainsString('تنبيه المصاريف غير المدفوعة', $alertHtml);
        $this->assertStringContainsString('لا توجد مصاريف غير مدفوعة ضمن الفلاتر الحالية', $alertHtml);
        $this->assertStringContainsString('0.00 ريال', $alertHtml);

        $this->assertStringNotContainsString('Unpaid hidden by paid filter', $alertHtml);
        $this->assertStringNotContainsString('500.00 ريال', $alertHtml);
        $this->assertStringNotContainsString('Paid visible in list but not unpaid alert', $alertHtml);
        $this->assertStringNotContainsString('300.00 ريال', $alertHtml);
    }

    private function unpaidAlertHtml(string $content): string
    {
        $startNeedle = '<div class="card" data-testid="expense-unpaid-alert"';
        $endNeedle = '<div class="card" data-testid="expense-monthly-summary"';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Unpaid expense alert section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Unpaid expense alert section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition);
    }

    private function withoutWhitespace(string $content): string
    {
        return preg_replace('/\s+/', '', $content) ?? $content;
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
            'code' => 'EXP-UNPAID-' . uniqid(),
            'description' => 'Test unpaid alert expense',
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
