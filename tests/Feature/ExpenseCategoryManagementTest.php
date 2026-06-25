<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_expense_category_pages(): void
    {
        $this->get('/expense-categories')->assertRedirect('/login');
        $this->get('/expense-categories/create')->assertRedirect('/login');
    }

    public function test_owner_can_view_expense_categories_index_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/expense-categories');

        $response->assertOk();
        $response->assertSee('تصنيفات المصاريف');
        $response->assertSee('مصاريف تشغيلية');
        $response->assertSee('operational-expenses');
        $response->assertSee('تصنيف جديد');
        $response->assertSee('تعديل');
        $response->assertSee('تعطيل');
    }

    public function test_owner_can_view_expense_category_create_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/expense-categories/create');

        $response->assertOk();
        $response->assertSee('تصنيف مصروف جديد');
        $response->assertSee('اسم التصنيف');
        $response->assertSee('Slug');
    }

    public function test_owner_can_create_expense_category(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $response = $this->actingAs($admin)->post('/expense-categories', [
            'name' => 'تسويق وإعلانات',
            'slug' => 'marketing-ads',
            'description' => 'مصاريف الحملات والإعلانات.',
        ]);

        $response->assertRedirect('/expense-categories');

        $this->assertDatabaseHas('expense_categories', [
            'company_id' => $branch->company_id,
            'name' => 'تسويق وإعلانات',
            'slug' => 'marketing-ads',
            'description' => 'مصاريف الحملات والإعلانات.',
            'is_active' => true,
        ]);
    }

    public function test_expense_category_slug_must_be_unique_per_company(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/expense-categories/create')
            ->post('/expense-categories', [
                'name' => 'تصنيف مكرر',
                'slug' => 'operational-expenses',
                'description' => 'محاولة تكرار slug موجود.',
            ]);

        $response->assertRedirect('/expense-categories/create');
        $response->assertSessionHasErrors('slug');
    }

    public function test_owner_can_view_expense_category_edit_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $response = $this->actingAs($admin)->get('/expense-categories/' . $category->id . '/edit');

        $response->assertOk();
        $response->assertSee('تعديل تصنيف مصروف');
        $response->assertSee('مصاريف تشغيلية');
        $response->assertSee('operational-expenses');
    }

    public function test_owner_can_update_expense_category(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $response = $this->actingAs($admin)->patch('/expense-categories/' . $category->id, [
            'name' => 'مصاريف تشغيلية محدثة',
            'slug' => 'operational-expenses-updated',
            'description' => 'وصف محدث للتصنيف.',
        ]);

        $response->assertRedirect('/expense-categories');

        $this->assertDatabaseHas('expense_categories', [
            'id' => $category->id,
            'name' => 'مصاريف تشغيلية محدثة',
            'slug' => 'operational-expenses-updated',
            'description' => 'وصف محدث للتصنيف.',
        ]);
    }

    public function test_expense_category_update_rejects_duplicate_slug(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $firstCategory = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();
        $secondCategory = ExpenseCategory::query()->where('slug', 'rent')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/expense-categories/' . $secondCategory->id . '/edit')
            ->patch('/expense-categories/' . $secondCategory->id, [
                'name' => 'محاولة تكرار',
                'slug' => $firstCategory->slug,
                'description' => 'slug مكرر.',
            ]);

        $response->assertRedirect('/expense-categories/' . $secondCategory->id . '/edit');
        $response->assertSessionHasErrors('slug');
    }

    public function test_owner_can_disable_and_enable_expense_category(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $disableResponse = $this->actingAs($admin)->patch('/expense-categories/' . $category->id . '/toggle-status');
        $disableResponse->assertRedirect('/expense-categories');

        $category->refresh();
        $this->assertFalse($category->is_active);

        $enableResponse = $this->actingAs($admin)->patch('/expense-categories/' . $category->id . '/toggle-status');
        $enableResponse->assertRedirect('/expense-categories');

        $category->refresh();
        $this->assertTrue($category->is_active);
    }

    public function test_disabled_expense_category_does_not_appear_in_expense_create_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $category->forceFill([
            'is_active' => false,
        ])->save();

        $response = $this->actingAs($admin)->get('/expenses/create');

        $response->assertOk();
        $response->assertDontSee('مصاريف تشغيلية');
    }

    public function test_expense_cannot_be_created_with_disabled_category(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $category->forceFill([
            'is_active' => false,
        ])->save();

        $response = $this->actingAs($admin)
            ->from('/expenses/create')
            ->post('/expenses', [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'مصروف على تصنيف معطل',
                'amount' => 99,
                'tax_amount' => 0,
                'payment_method' => 'cash',
                'expense_date' => now()->toDateString(),
            ]);

        $response->assertRedirect('/expenses/create');
        $response->assertSessionHasErrors('expense_category_id');

        $this->assertDatabaseMissing('expenses', [
            'description' => 'مصروف على تصنيف معطل',
        ]);
    }

    public function test_new_expense_category_appears_in_expense_create_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        ExpenseCategory::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'صيانة وتشغيل',
            'slug' => 'maintenance-operations',
            'description' => 'مصاريف الصيانة والتشغيل.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/expenses/create');

        $response->assertOk();
        $response->assertSee('صيانة وتشغيل');
    }

    public function test_owner_can_create_expense_using_new_category(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();

        $category = ExpenseCategory::query()->create([
            'company_id' => $branch->company_id,
            'name' => 'مشتريات مكتبية',
            'slug' => 'office-purchases',
            'description' => 'مصاريف مشتريات مكتبية.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post('/expenses', [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'شراء أدوات مكتبية',
            'amount' => 99.75,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
            'reference_number' => 'OFFICE-001',
            'notes' => 'مصروف مربوط بتصنيف جديد.',
        ]);

        $response->assertRedirect('/expenses');

        $this->assertDatabaseHas('expenses', [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'شراء أدوات مكتبية',
            'amount' => 99.75,
            'reference_number' => 'OFFICE-001',
        ]);
    }

    public function test_expense_category_index_shows_expenses_count(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        Expense::query()->create([
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'user_id' => $admin->id,
            'code' => 'EXP-CAT-COUNT',
            'description' => 'مصروف لاختبار عدد التصنيف',
            'amount' => 45,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
            'is_paid' => true,
        ]);

        $response = $this->actingAs($admin)->get('/expense-categories');

        $response->assertOk();
        $response->assertSee('مصاريف تشغيلية');
        $response->assertSee('1');
    }
}
