<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeUnpaidSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_large_unpaid_summary_shows_count_and_total_respecting_current_filters(): void
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

        $this->createExpense($company, $visibleBranch, $category, 'Visible large unpaid summary expense 1800', 1800, 'cash', false, '2026-06-25');
        $this->createExpense($company, $visibleBranch, $category, 'Visible large unpaid summary expense 1200', 1200, 'cash', false, '2026-06-24');

        $this->createExpense($company, $visibleBranch, $category, 'Hidden large paid summary expense 5000', 5000, 'cash', true, '2026-06-23');
        $this->createExpense($company, $visibleBranch, $category, 'Hidden small unpaid summary expense 999', 999, 'cash', false, '2026-06-22');
        $this->createExpense($company, $hiddenBranch, $category, 'Hidden branch large unpaid summary expense 4000', 4000, 'cash', false, '2026-06-21');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $visibleBranch->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $summaryHtml = $this->largeUnpaidSummaryHtml($response->getContent());

        $this->assertStringContainsString('ملخص المصاريف الكبيرة غير المدفوعة', $summaryHtml);
        $this->assertStringContainsString('expense-large-unpaid-summary-count">2</strong>', $summaryHtml);
        $this->assertStringContainsString('3,000.00 ريال', $summaryHtml);

        $this->assertStringNotContainsString('6,000.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('7,000.00 ريال', $summaryHtml);
        $this->assertStringNotContainsString('7,999.00 ريال', $summaryHtml);
    }

    public function test_large_unpaid_summary_respects_payment_status_filter(): void
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

        $this->createExpense($company, $branch, $category, 'Hidden by paid filter unpaid expense', 1800, 'cash', false, '2026-06-25');
        $this->createExpense($company, $branch, $category, 'Paid filter large paid expense', 2500, 'cash', true, '2026-06-24');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'payment_status' => 'paid',
        ]));

        $response->assertOk();

        $summaryHtml = $this->largeUnpaidSummaryHtml($response->getContent());

        $this->assertStringContainsString('expense-large-unpaid-summary-count">0</strong>', $summaryHtml);
        $this->assertStringContainsString('0.00 ريال', $summaryHtml);
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

    private function largeUnpaidSummaryHtml(string $content): string
    {
        $startNeedle = '<div class="card" data-testid="expense-large-unpaid-summary"';
        $endNeedle = '<div class="card" data-testid="expense-large-amount-top-list"';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Large unpaid summary section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Large unpaid summary section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition);
    }
}
