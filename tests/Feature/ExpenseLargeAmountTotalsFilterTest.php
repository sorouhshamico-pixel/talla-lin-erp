<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountTotalsFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_totals_reflect_large_amount_filter_when_active(): void
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
            'description' => 'Small totals filter expense',
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'expense_date' => '2026-06-20',
        ]);

        Expense::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'Large totals filter expense',
            'amount' => 1500,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'expense_date' => '2026-06-21',
        ]);

        $unfilteredResponse = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
        ]));

        $unfilteredResponse->assertOk();
        $unfilteredResponse->assertSee('2,000.00 ريال');

        $filteredResponse = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'large_amount' => '1',
        ]));

        $filteredResponse->assertOk();

        // The summary cards must show only the large expense's totals, not both.
        $filteredResponse->assertSee('1,500.00 ريال');
        $filteredResponse->assertDontSee('2,000.00 ريال');
    }
}
