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

class ExpenseMissingAttachmentSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_missing_attachment_summary_respecting_current_filters(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-MISSING-ATT-001');

        $branch = $this->branch($companyId, 'Missing Attachment Branch', 'BR-MISSING-ATT-001');
        $otherBranch = $this->branch($companyId, 'Other Missing Attachment Branch', 'BR-MISSING-ATT-002');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Missing Attachment Category',
            'slug' => 'missing-attachment-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Visible missing attachment expense',
            'amount' => 300,
            'payment_method' => 'cash',
            'is_paid' => true,
            'attachment_path' => null,
            'attachment_original_name' => null,
        ]);

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Hidden attached expense',
            'amount' => 900,
            'payment_method' => 'cash',
            'is_paid' => true,
            'attachment_path' => 'expense-attachments/attached.pdf',
            'attachment_original_name' => 'attached.pdf',
        ]);

        $this->expense($companyId, $otherBranch->id, $category->id, [
            'description' => 'Other branch missing attachment expense',
            'amount' => 800,
            'payment_method' => 'cash',
            'is_paid' => true,
            'attachment_path' => null,
            'attachment_original_name' => null,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $summaryHtml = $this->missingAttachmentSummaryHtml($response->getContent());

        $this->assertStringContainsString('ملخص المصاريف بدون مرفق', $summaryHtml);
        $this->assertStringContainsString('عدد المصاريف بدون مرفق', $summaryHtml);
        $this->assertStringContainsString('>1<', $this->withoutWhitespace($summaryHtml));
        $this->assertStringContainsString('إجمالي قيمة المصاريف بدون مرفق', $summaryHtml);
        $this->assertStringContainsString('300.00 ريال', $summaryHtml);

        $this->assertStringNotContainsString('900.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('800.00 ريال', $summaryHtml);
    }

    public function test_missing_attachment_summary_respects_existing_attachment_filter(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-MISSING-ATT-101');

        $branch = $this->branch($companyId, 'Missing Attachment Existing Filter Branch', 'BR-MISSING-ATT-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Existing Attachment Filter Category',
            'slug' => 'existing-attachment-filter-category',
        ]));

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Attached expense should make missing summary zero',
            'amount' => 500,
            'payment_method' => 'bank_transfer',
            'is_paid' => true,
            'attachment_path' => 'expense-attachments/existing-attached.pdf',
            'attachment_original_name' => 'existing-attached.pdf',
        ]);

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Missing attachment hidden by with attachment filter',
            'amount' => 250,
            'payment_method' => 'bank_transfer',
            'is_paid' => true,
            'attachment_path' => null,
            'attachment_original_name' => null,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_method' => 'bank_transfer',
            'attachment_status' => 'with_attachment',
        ]));

        $response->assertOk();

        $summaryHtml = $this->missingAttachmentSummaryHtml($response->getContent());

        $this->assertStringContainsString('ملخص المصاريف بدون مرفق', $summaryHtml);
        $this->assertStringContainsString('>0<', $this->withoutWhitespace($summaryHtml));
        $this->assertStringContainsString('0.00 ريال', $summaryHtml);

        $this->assertStringNotContainsString('500.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('250.00 ريال', $summaryHtml);
    }

    public function test_missing_attachment_quick_filter_link_preserves_current_filters_and_overwrites_attachment_status(): void
    {
        $user = User::factory()->create();

        $companyId = $this->companyId('COMP-MISSING-ATT-201');

        $branch = $this->branch($companyId, 'Missing Attachment Quick Filter Branch', 'BR-MISSING-ATT-201');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Missing Attachment Quick Filter Category',
            'slug' => 'missing-attachment-quick-filter-category',
        ]));

        $fromDate = now()->subDays(15)->toDateString();
        $toDate = now()->toDateString();

        $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Quick missing attachment expense',
            'amount' => 650,
            'payment_method' => 'cash',
            'is_paid' => false,
            'expense_date' => now()->subDays(3)->toDateString(),
            'attachment_path' => null,
            'attachment_original_name' => null,
        ]);

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'attachment_status' => 'with_attachment',
        ]));

        $response->assertOk();

        $summaryHtml = $this->missingAttachmentSummaryHtml($response->getContent());

        $this->assertStringContainsString('عرض المصاريف بدون مرفق', $summaryHtml);
        $this->assertStringContainsString('from_date=' . $fromDate, $summaryHtml);
        $this->assertStringContainsString('to_date=' . $toDate, $summaryHtml);
        $this->assertStringContainsString('branch_id=' . $branch->id, $summaryHtml);
        $this->assertStringContainsString('expense_category_id=' . $category->id, $summaryHtml);
        $this->assertStringContainsString('payment_method=cash', $summaryHtml);
        $this->assertStringContainsString('payment_status=unpaid', $summaryHtml);
        $this->assertStringContainsString('attachment_status=without_attachment', $summaryHtml);
        $this->assertStringNotContainsString('attachment_status=with_attachment', $summaryHtml);
    }

    private function missingAttachmentSummaryHtml(string $content): string
    {
        $startNeedle = '<div class="card" data-testid="expense-missing-attachment-summary"';
        $endNeedle = '<div class="card" data-testid="expense-list"';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Missing attachment summary section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Missing attachment summary section end was not found.');

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
            'code' => 'EXP-MISSING-ATT-' . uniqid(),
            'description' => 'Test missing attachment summary expense',
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
