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

class ExpenseAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_expense_with_attachment(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Attachment Branch', 'BR-ATT-001');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Attachment Category',
            'slug' => 'attachment-category',
        ]));

        $this->actingAs($user)
            ->post(route('expenses.store'), [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'Expense with receipt attachment',
                'amount' => 500,
                'tax_amount' => 75,
                'payment_method' => 'cash',
                'is_paid' => '1',
                'expense_date' => now()->toDateString(),
                'reference_number' => 'ATT-001',
                'notes' => 'Receipt attached',
                'attachment' => UploadedFile::fake()->create('receipt.pdf', 256, 'application/pdf'),
            ])
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::query()
            ->where('description', 'Expense with receipt attachment')
            ->firstOrFail();

        $this->assertNotNull($expense->attachment_path);
        $this->assertSame('receipt.pdf', $expense->attachment_original_name);

        Storage::disk('public')->assertExists($expense->attachment_path);

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('عرض المرفق');
        $response->assertSee('receipt.pdf');
    }

    public function test_owner_can_replace_expense_attachment_on_update(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Attachment Update Branch', 'BR-ATT-101');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Attachment Update Category',
            'slug' => 'attachment-update-category',
        ]));

        $oldPath = UploadedFile::fake()
            ->create('old-receipt.pdf', 128, 'application/pdf')
            ->store('expense-attachments', 'public');

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense before attachment replace',
            'attachment_path' => $oldPath,
            'attachment_original_name' => 'old-receipt.pdf',
        ]);

        Storage::disk('public')->assertExists($oldPath);

        $this->actingAs($user)
            ->patch(route('expenses.update', $expense), [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'Expense after attachment replace',
                'amount' => 650,
                'tax_amount' => 97.5,
                'payment_method' => 'bank_transfer',
                'is_paid' => '0',
                'expense_date' => now()->toDateString(),
                'reference_number' => 'ATT-UPDATED-001',
                'notes' => 'Attachment replaced',
                'attachment' => UploadedFile::fake()->image('new-receipt.jpg', 800, 600),
            ])
            ->assertRedirect(route('expenses.index'));

        $expense->refresh();

        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($expense->attachment_path);

        $this->assertSame('new-receipt.jpg', $expense->attachment_original_name);
        $this->assertFalse($expense->is_paid);

        $response = $this->actingAs($user)->get(route('expenses.edit', $expense));

        $response->assertOk();
        $response->assertSee('عرض المرفق الحالي');
        $response->assertSee('new-receipt.jpg');
    }

    public function test_expense_attachment_must_be_allowed_file_type(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Attachment Validation Branch', 'BR-ATT-201');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Attachment Validation Category',
            'slug' => 'attachment-validation-category',
        ]));

        $this->actingAs($user)
            ->from(route('expenses.create'))
            ->post(route('expenses.store'), [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'Expense with invalid attachment',
                'amount' => 100,
                'tax_amount' => 15,
                'payment_method' => 'cash',
                'is_paid' => '1',
                'expense_date' => now()->toDateString(),
                'attachment' => UploadedFile::fake()->create('invalid.exe', 128, 'application/octet-stream'),
            ])
            ->assertSessionHasErrors('attachment');

        $this->assertDatabaseMissing('expenses', [
            'description' => 'Expense with invalid attachment',
        ]);
    }

    public function test_deleting_expense_deletes_attachment_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $companyId = $this->companyId();
        $branch = $this->branch($companyId, 'Main Attachment Delete Branch', 'BR-ATT-301');

        $category = ExpenseCategory::query()->create($this->expenseCategoryData($companyId, [
            'name' => 'Attachment Delete Category',
            'slug' => 'attachment-delete-category',
        ]));

        $path = UploadedFile::fake()
            ->create('delete-me.pdf', 64, 'application/pdf')
            ->store('expense-attachments', 'public');

        $expense = $this->expense($companyId, $branch->id, $category->id, [
            'description' => 'Expense with file to delete',
            'attachment_path' => $path,
            'attachment_original_name' => 'delete-me.pdf',
        ]);

        Storage::disk('public')->assertExists($path);

        $this->actingAs($user)
            ->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));

        Storage::disk('public')->assertMissing($path);

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
            $data['code'] = 'COMP-ATT-001';
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
            'code' => 'EXP-ATT-' . uniqid(),
            'description' => 'Test attachment expense',
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
