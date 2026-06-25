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

class ExpenseCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_export_expenses_csv_with_current_filters(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-CSV-001');

        $mainBranch = $this->branch($companyId, 'Main CSV Branch', 'BR-CSV-001');
        $otherBranch = $this->branch($companyId, 'Other CSV Branch', 'BR-CSV-002');

        $rentCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'CSV Rent Category',
            'slug' => 'csv-rent-category',
        ]));

        $fuelCategory = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'CSV Fuel Category',
            'slug' => 'csv-fuel-category',
        ]));

        $today = now()->toDateString();
        $oldDate = now()->subDays(7)->toDateString();

        $this->expense($companyId, $mainBranch->id, $rentCategory->id, [
            'code' => 'EXP-CSV-000001',
            'description' => 'CSV visible unpaid rent expense',
            'amount' => 300,
            'tax_amount' => 45,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => $today,
            'reference_number' => 'CSV-REF-001',
            'attachment_original_name' => 'visible-receipt.pdf',
            'attachment_path' => 'expense-attachments/visible-receipt.pdf',
        ]);

        $this->expense($companyId, $otherBranch->id, $rentCategory->id, [
            'code' => 'EXP-CSV-000002',
            'description' => 'CSV hidden other branch expense',
            'amount' => 100,
            'tax_amount' => 15,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => $today,
        ]);

        $this->expense($companyId, $mainBranch->id, $fuelCategory->id, [
            'code' => 'EXP-CSV-000003',
            'description' => 'CSV hidden other category expense',
            'amount' => 200,
            'tax_amount' => 30,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => $today,
        ]);

        $this->expense($companyId, $mainBranch->id, $rentCategory->id, [
            'code' => 'EXP-CSV-000004',
            'description' => 'CSV hidden old date expense',
            'amount' => 90,
            'tax_amount' => 13.5,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => $oldDate,
        ]);

        $this->expense($companyId, $mainBranch->id, $rentCategory->id, [
            'code' => 'EXP-CSV-000005',
            'description' => 'CSV hidden paid expense',
            'amount' => 110,
            'tax_amount' => 16.5,
            'payment_method' => 'cash',
            'is_paid' => true,
            'expense_date' => $today,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.export', [
            'from_date' => $today,
            'to_date' => $today,
            'branch_id' => $mainBranch->id,
            'expense_category_id' => $rentCategory->id,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();

        $contentDisposition = $response->headers->get('content-disposition');

        $this->assertIsString($contentDisposition);
        $this->assertStringContainsString('attachment;', $contentDisposition);
        $this->assertStringContainsString('expenses-report-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $content = $response->streamedContent();

        $this->assertStringContainsString('الكود', $content);
        $this->assertStringContainsString('التاريخ', $content);
        $this->assertStringContainsString('الوصف', $content);
        $this->assertStringContainsString('حالة الدفع', $content);

        $this->assertStringContainsString('CSV visible unpaid rent expense', $content);
        $this->assertStringContainsString('CSV Rent Category', $content);
        $this->assertStringContainsString('نقدًا', $content);
        $this->assertStringContainsString('غير مدفوع', $content);
        $this->assertStringContainsString('300.00', $content);
        $this->assertStringContainsString('45.00', $content);
        $this->assertStringContainsString('CSV-REF-001', $content);
        $this->assertStringContainsString('visible-receipt.pdf', $content);

        $this->assertStringNotContainsString('CSV hidden other branch expense', $content);
        $this->assertStringNotContainsString('CSV hidden other category expense', $content);
        $this->assertStringNotContainsString('CSV hidden old date expense', $content);
        $this->assertStringNotContainsString('CSV hidden paid expense', $content);
    }

    public function test_expenses_index_shows_csv_export_link_with_current_filters(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-CSV-101');
        $branch = $this->branch($companyId, 'CSV Link Branch', 'BR-CSV-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'CSV Link Category',
            'slug' => 'csv-link-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'CSV link visible expense',
            'amount' => 75,
            'tax_amount' => 11.25,
            'payment_method' => 'bank_transfer',
            'is_paid' => true,
        ]);

        $exportUrl = route('expenses.export', [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'paid',
        ]));

        $response->assertOk();
        $response->assertSee('تصدير CSV');
        $response->assertSee($exportUrl);
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
            'code' => 'EXP-CSV-' . uniqid(),
            'description' => 'Test csv expense',
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
