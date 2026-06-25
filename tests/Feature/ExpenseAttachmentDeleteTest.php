<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseAttachmentDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_expense_attachment_without_deleting_expense(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Attachment Remove Branch', 'BR-ADR-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Attachment Remove Category',
            'slug' => 'attachment-remove-category',
        ]));

        $path = UploadedFile::fake()
            ->create('remove-this.pdf', 128, 'application/pdf')
            ->store('expense-attachments', 'public');

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense keeps record after attachment delete',
            'attachment_path' => $path,
            'attachment_original_name' => 'remove-this.pdf',
        ]);

        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->delete(route('expenses.attachment.destroy', $expense))
            ->assertRedirect(route('expenses.edit', $expense));

        Storage::disk('public')->assertMissing($path);

        $expense->refresh();

        $this->assertNull($expense->attachment_path);
        $this->assertNull($expense->attachment_original_name);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Expense keeps record after attachment delete',
        ]);
    }

    public function test_edit_page_shows_delete_attachment_button_when_attachment_exists(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Attachment Button Branch', 'BR-ADR-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Attachment Button Category',
            'slug' => 'attachment-button-category',
        ]));

        $path = UploadedFile::fake()
            ->create('current-receipt.pdf', 128, 'application/pdf')
            ->store('expense-attachments', 'public');

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense with visible delete attachment button',
            'attachment_path' => $path,
            'attachment_original_name' => 'current-receipt.pdf',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.edit', $expense));

        $response->assertOk();
        $response->assertSee('المرفق الحالي');
        $response->assertSee('عرض المرفق الحالي');
        $response->assertSee('حذف المرفق');
        $response->assertSee('current-receipt.pdf');
    }

    public function test_deleting_missing_attachment_is_safe_and_keeps_expense(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Missing Attachment Branch', 'BR-ADR-201');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Missing Attachment Category',
            'slug' => 'missing-attachment-category',
        ]));

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense without attachment',
            'attachment_path' => null,
            'attachment_original_name' => null,
        ]);

        $this->actingAs($user)
            ->delete(route('expenses.attachment.destroy', $expense))
            ->assertRedirect(route('expenses.edit', $expense));

        $expense->refresh();

        $this->assertNull($expense->attachment_path);
        $this->assertNull($expense->attachment_original_name);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'description' => 'Expense without attachment',
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
            $data['code'] = 'COMP-ADR-001';
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
            'code' => 'EXP-ADR-' . uniqid(),
            'description' => 'Test attachment delete expense',
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
