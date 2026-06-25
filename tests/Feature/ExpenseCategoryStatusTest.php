<?php

namespace Tests\Feature;

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExpenseCategoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_category_can_be_edited_and_slug_must_be_unique_on_update(): void
    {
        $user = User::factory()->create();

        $firstCategory = ExpenseCategory::query()->create($this->expenseCategoryData([
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
            'description' => 'Office expenses',
            'is_active' => true,
        ]));

        $secondCategory = ExpenseCategory::query()->create($this->expenseCategoryData([
            'name' => 'Fuel',
            'slug' => 'fuel',
            'description' => 'Fuel expenses',
            'is_active' => true,
        ]));

        $this->actingAs($user)
            ->from(route('expense-categories.edit', $secondCategory))
            ->patch(route('expense-categories.update', $secondCategory), [
                'name' => 'Fuel Updated',
                'slug' => $firstCategory->slug,
                'description' => 'Updated fuel expenses',
                'is_active' => '1',
            ])
            ->assertRedirect(route('expense-categories.edit', $secondCategory));

        $this->assertDatabaseHas('expense_categories', [
            'id' => $secondCategory->id,
            'name' => 'Fuel',
            'slug' => 'fuel',
        ]);

        $this->assertDatabaseMissing('expense_categories', [
            'id' => $secondCategory->id,
            'slug' => $firstCategory->slug,
        ]);

        $this->actingAs($user)
            ->patch(route('expense-categories.update', $secondCategory), [
                'name' => 'Fuel Updated',
                'slug' => 'fuel-updated',
                'description' => 'Updated fuel expenses',
                'is_active' => '1',
            ])
            ->assertRedirect(route('expense-categories.index'));

        $this->assertDatabaseHas('expense_categories', [
            'id' => $secondCategory->id,
            'name' => 'Fuel Updated',
            'slug' => 'fuel-updated',
            'description' => 'Updated fuel expenses',
            'is_active' => true,
        ]);
    }

    public function test_expense_category_can_be_disabled_and_enabled(): void
    {
        $user = User::factory()->create();

        $category = ExpenseCategory::query()->create($this->expenseCategoryData([
            'name' => 'Maintenance',
            'slug' => 'maintenance',
            'description' => 'Maintenance expenses',
            'is_active' => true,
        ]));

        $this->actingAs($user)
            ->from(route('expense-categories.index'))
            ->patch(route('expense-categories.toggle', $category))
            ->assertRedirect(route('expense-categories.index'));

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->from(route('expense-categories.index'))
            ->patch(route('expense-categories.toggle', $category))
            ->assertRedirect(route('expense-categories.index'));

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'is_active' => true,
        ]);
    }

    public function test_inactive_expense_categories_are_hidden_from_expense_create_page(): void
    {
        $user = User::factory()->create();

        ExpenseCategory::query()->create($this->expenseCategoryData([
            'name' => 'Visible Active Category',
            'slug' => 'visible-active-category',
            'description' => 'Active category',
            'is_active' => true,
        ]));

        ExpenseCategory::query()->create($this->expenseCategoryData([
            'name' => 'Hidden Inactive Category',
            'slug' => 'hidden-inactive-category',
            'description' => 'Inactive category',
            'is_active' => false,
        ]));

        $this->actingAs($user)
            ->get(route('expenses.create'))
            ->assertOk()
            ->assertSee('Visible Active Category')
            ->assertDontSee('Hidden Inactive Category');
    }

    public function test_inactive_expense_category_cannot_be_used_when_storing_expense_manually(): void
    {
        $user = User::factory()->create();

        $this->seed();

        $branchId = DB::table('branches')->value('id') ?: $this->createBranchId();

        $inactiveCategory = ExpenseCategory::query()->create($this->expenseCategoryData([
            'name' => 'Inactive Manual Category',
            'slug' => 'inactive-manual-category',
            'description' => 'Inactive category',
            'is_active' => false,
        ]));

        $payload = [
            'branch_id' => $branchId,
            'expense_category_id' => $inactiveCategory->id,
            'expense_date' => now()->toDateString(),
            'date' => now()->toDateString(),
            'amount' => 250,
            'payment_method' => 'cash',
            'description' => 'Manual inactive category test',
            'notes' => 'Manual inactive category test',
        ];

        $this->actingAs($user)
            ->from(route('expenses.create'))
            ->post(route('expenses.store'), $payload)
            ->assertSessionHasErrors('expense_category_id');
    }

    private function expenseCategoryData(array $overrides = []): array
    {
        $data = [];

        if (Schema::hasColumn('expense_categories', 'company_id')) {
            $data['company_id'] = $this->createCompanyId();
        }

        return array_merge($data, $overrides);
    }

    private function createBranchId(): int
    {
        $existingBranchId = DB::table('branches')->value('id');

        if ($existingBranchId) {
            return (int) $existingBranchId;
        }

        $columns = Schema::getColumnListing('branches');

        $data = [];

        if (in_array('company_id', $columns, true)) {
            $data['company_id'] = $this->createCompanyId();
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

        if (in_array('address', $columns, true)) {
            $data['address'] = 'Riyadh';
        }

        if (in_array('city', $columns, true)) {
            $data['city'] = 'Riyadh';
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

        $data = $this->fillSqliteRequiredColumns('branches', $data);

        return DB::table('branches')->insertGetId($data);
    }

    private function createCompanyId(): int
    {
        if (! Schema::hasTable('companies')) {
            return 1;
        }

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

        if (in_array('commercial_registration_number', $columns, true)) {
            $data['commercial_registration_number'] = '1010000001';
        }

        if (in_array('cr_number', $columns, true)) {
            $data['cr_number'] = '1010000001';
        }

        if (in_array('tax_number', $columns, true)) {
            $data['tax_number'] = '300000000000003';
        }

        if (in_array('vat_number', $columns, true)) {
            $data['vat_number'] = '300000000000003';
        }

        if (in_array('phone', $columns, true)) {
            $data['phone'] = '0500000000';
        }

        if (in_array('email', $columns, true)) {
            $data['email'] = 'test-company@example.test';
        }

        if (in_array('address', $columns, true)) {
            $data['address'] = 'Riyadh';
        }

        if (in_array('city', $columns, true)) {
            $data['city'] = 'Riyadh';
        }

        if (in_array('country', $columns, true)) {
            $data['country'] = 'Saudi Arabia';
        }

        if (in_array('currency', $columns, true)) {
            $data['currency'] = 'SAR';
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

        $data = $this->fillSqliteRequiredColumns('companies', $data);

        return DB::table('companies')->insertGetId($data);
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
