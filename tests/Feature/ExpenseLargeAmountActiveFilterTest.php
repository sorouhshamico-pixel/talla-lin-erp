<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountActiveFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_amount_active_filter_notice_appears_only_when_filter_is_active(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $category = ExpenseCategory::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        Expense::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'Large active filter expense',
            'amount' => 1500,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'expense_date' => '2026-06-20',
        ]);

        $inactiveResponse = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
        ]));

        $inactiveResponse->assertOk();
        $inactiveResponse->assertDontSee('فلتر المصاريف الكبيرة مفعّل');

        $activeResponse = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'large_amount' => '1',
        ]));

        $activeResponse->assertOk();
        $activeResponse->assertSee('فلتر المصاريف الكبيرة مفعّل');
        $activeResponse->assertSee('إلغاء فلتر المصاريف الكبيرة');

        $expectedResetUrl = route('expenses.index', [
            'branch_id' => $branch->id,
        ]);

        $activeResponse->assertSee(e($expectedResetUrl), false);
        $activeResponse->assertSee('Large active filter expense');
    }
}
