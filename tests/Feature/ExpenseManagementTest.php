<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_expenses_pages(): void
    {
        $this->get('/expenses')->assertRedirect('/login');
        $this->get('/expenses/create')->assertRedirect('/login');
    }

    public function test_owner_can_view_expenses_create_page(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->get('/expenses/create');

        $response->assertOk();
        $response->assertSee('مصروف جديد');
        $response->assertSee('مصاريف تشغيلية');
        $response->assertSee('الفرع الرئيسي');
    }

    public function test_owner_can_create_expense_from_form(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $response = $this->actingAs($admin)->post('/expenses', [
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'مصروف تشغيل تجريبي',
            'amount' => 250.50,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
            'reference_number' => 'EXP-REF-001',
            'notes' => 'مصروف لاختبار النظام.',
        ]);

        $response->assertRedirect('/expenses');

        $this->assertDatabaseHas('expenses', [
            'company_id' => $branch->company_id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'مصروف تشغيل تجريبي',
            'amount' => 250.50,
            'payment_method' => 'cash',
            'reference_number' => 'EXP-REF-001',
            'is_paid' => true,
        ]);
    }

    public function test_expense_form_rejects_zero_amount(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();
        $branch = Branch::query()->where('code', 'MAIN')->firstOrFail();
        $category = ExpenseCategory::query()->where('slug', 'operational-expenses')->firstOrFail();

        $response = $this->actingAs($admin)
            ->from('/expenses/create')
            ->post('/expenses', [
                'branch_id' => $branch->id,
                'expense_category_id' => $category->id,
                'description' => 'مصروف غير صحيح',
                'amount' => 0,
                'tax_amount' => 0,
                'payment_method' => 'cash',
                'expense_date' => now()->toDateString(),
            ]);

        $response->assertRedirect('/expenses/create');
        $response->assertSessionHasErrors('amount');
    }

    public function test_owner_can_view_expenses_index_page(): void
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
            'code' => 'EXP-TEST-001',
            'description' => 'مصروف ظاهر في القائمة',
            'amount' => 150,
            'tax_amount' => 0,
            'payment_method' => 'bank_transfer',
            'expense_date' => now()->toDateString(),
            'is_paid' => true,
        ]);

        $response = $this->actingAs($admin)->get('/expenses');

        $response->assertOk();
        $response->assertSee('المصاريف التشغيلية');
        $response->assertSee('مصروف ظاهر في القائمة');
        $response->assertSee('150.00');
        $response->assertSee('تحويل بنكي');
    }

    public function test_reports_include_operating_expenses_and_profit_after_expenses(): void
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
            'code' => 'EXP-REPORT-001',
            'description' => 'مصروف تقرير',
            'amount' => 150,
            'tax_amount' => 0,
            'payment_method' => 'cash',
            'expense_date' => now()->toDateString(),
            'is_paid' => true,
        ]);

        $response = $this->actingAs($admin)->get('/reports');

        $response->assertOk();
        $response->assertSee('تقرير المصاريف التشغيلية');
        $response->assertSee('إجمالي المصاريف التشغيلية');
        $response->assertSee('الربح بعد المصاريف');
        $response->assertSee('150.00');
        $response->assertSee('-150.00');
    }
}
