<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountTopListTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_top_five_large_expenses_respecting_current_filters(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $visibleBranch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $hiddenBranch = Branch::query()
            ->where('company_id', $company->id)
            ->whereKeyNot($visibleBranch->id)
            ->orderBy('id')
            ->firstOrFail();

        $category = ExpenseCategory::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $this->createExpense($company, $visibleBranch, $category, 'Top large expense 5000', 5000, 'cash', true, '2026-06-25');
        $this->createExpense($company, $visibleBranch, $category, 'Top large expense 4000', 4000, 'cash', true, '2026-06-24');
        $this->createExpense($company, $visibleBranch, $category, 'Top large expense 3000', 3000, 'cash', true, '2026-06-23');
        $this->createExpense($company, $visibleBranch, $category, 'Top large expense 2000', 2000, 'cash', true, '2026-06-22');
        $this->createExpense($company, $visibleBranch, $category, 'Top large expense 1500', 1500, 'cash', true, '2026-06-21');
        $this->createExpense($company, $visibleBranch, $category, 'Sixth large expense 1200', 1200, 'cash', true, '2026-06-20');
        $this->createExpense($company, $visibleBranch, $category, 'Small expense 999', 999, 'cash', true, '2026-06-19');
        $this->createExpense($company, $hiddenBranch, $category, 'Hidden branch large expense 8000', 8000, 'cash', true, '2026-06-18');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $visibleBranch->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $topListHtml = $this->largeAmountTopListHtml($response->getContent());

        $this->assertStringContainsString('أعلى 5 مصاريف كبيرة', $topListHtml);
        $this->assertStringContainsString('Top large expense 5000', $topListHtml);
        $this->assertStringContainsString('Top large expense 4000', $topListHtml);
        $this->assertStringContainsString('Top large expense 3000', $topListHtml);
        $this->assertStringContainsString('Top large expense 2000', $topListHtml);
        $this->assertStringContainsString('Top large expense 1500', $topListHtml);

        $this->assertStringNotContainsString('Sixth large expense 1200', $topListHtml);
        $this->assertStringNotContainsString('Small expense 999', $topListHtml);
        $this->assertStringNotContainsString('Hidden branch large expense 8000', $topListHtml);
    }

    public function test_top_large_expenses_summary_respects_payment_status_filter(): void
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

        $this->createExpense($company, $branch, $category, 'Visible unpaid top large expense', 1800, 'cash', false, '2026-06-25');
        $this->createExpense($company, $branch, $category, 'Hidden paid top large expense', 3000, 'cash', true, '2026-06-25');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();

        $topListHtml = $this->largeAmountTopListHtml($response->getContent());

        $this->assertStringContainsString('Visible unpaid top large expense', $topListHtml);
        $this->assertStringNotContainsString('Hidden paid top large expense', $topListHtml);
    }

    private function createExpense(
        Company $company,
        Branch $branch,
        ExpenseCategory $category,
        string $description,
        float $amount,
        string $paymentMethod,
        bool $isPaid,
        string $expenseDate
    ): Expense {
        return Expense::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => $description,
            'amount' => $amount,
            'tax_amount' => 0,
            'payment_method' => $paymentMethod,
            'is_paid' => $isPaid,
            'expense_date' => $expenseDate,
        ]);
    }

    private function largeAmountTopListHtml(string $content): string
    {
        $startNeedle = '<div class="card" data-testid="expense-large-amount-top-list"';
        $endNeedle = '<div class="card" data-testid="expense-unpaid-alert"';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Large amount top list section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Large amount top list section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition);
    }
}
