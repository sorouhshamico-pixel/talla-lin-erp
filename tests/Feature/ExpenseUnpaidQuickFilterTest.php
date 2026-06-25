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

class ExpenseUnpaidQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_unpaid_alert_has_quick_filter_link_preserving_current_filters(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-QUICK-001');

        $branch = $this->branch($companyId, 'Quick Filter Branch', 'BR-QUICK-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Quick Filter Category',
            'slug' => 'quick-filter-category',
        ]));

        $fromDate = now()->subDays(10)->toDateString();
        $toDate = now()->toDateString();

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Quick filter unpaid expense',
            'amount' => 500,
            'payment_method' => 'bank_transfer',
            'is_paid' => false,
            'expense_date' => now()->subDays(2)->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'bank_transfer',
        ]));

        $response->assertOk();

        $alertHtml = $this->unpaidAlertHtml($response->getContent());

        $this->assertStringContainsString('عرض المصاريف غير المدفوعة', $alertHtml);
        $this->assertStringContainsString('payment_status=unpaid', $alertHtml);
        $this->assertStringContainsString('from_date=' . $fromDate, $alertHtml);
        $this->assertStringContainsString('to_date=' . $toDate, $alertHtml);
        $this->assertStringContainsString('branch_id=' . $branch->id, $alertHtml);
        $this->assertStringContainsString('expense_category_id=' . $category->id, $alertHtml);
        $this->assertStringContainsString('payment_method=bank_transfer', $alertHtml);
    }

    public function test_unpaid_quick_filter_overwrites_existing_paid_status(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-QUICK-101');

        $branch = $this->branch($companyId, 'Quick Paid Override Branch', 'BR-QUICK-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Quick Paid Override Category',
            'slug' => 'quick-paid-override-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Paid status override visible expense',
            'amount' => 250,
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

        $this->assertStringContainsString('عرض المصاريف غير المدفوعة', $alertHtml);
        $this->assertStringContainsString('branch_id=' . $branch->id, $alertHtml);
        $this->assertStringContainsString('payment_status=unpaid', $alertHtml);
        $this->assertStringNotContainsString('payment_status=paid', $alertHtml);
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
            'code' => 'EXP-QUICK-' . uniqid(),
            'description' => 'Test quick filter expense',
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
