<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountTopListVisualTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_large_expenses_summary_shows_ranking_and_highlights_first_expense(): void
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

        $this->createExpense($company, $branch, $category, 'Top visual expense 2500', 2500, 'cash', true, '2026-06-25');
        $this->createExpense($company, $branch, $category, 'Second visual expense 1500', 1500, 'cash', true, '2026-06-24');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
        ]));

        $response->assertOk();

        $topListHtml = $this->largeAmountTopListHtml($response->getContent());

        $this->assertStringContainsString('<th>الترتيب</th>', $topListHtml);
        $this->assertStringContainsString('expense-large-amount-top-expense-row-1', $topListHtml);
        $this->assertStringContainsString('expense-large-amount-top-expense-row-2', $topListHtml);
        $this->assertStringContainsString('#1', $topListHtml);
        $this->assertStringContainsString('#2', $topListHtml);
        $this->assertStringContainsString('الأعلى', $topListHtml);
        $this->assertStringContainsString('Top visual expense 2500', $topListHtml);
        $this->assertStringContainsString('Second visual expense 1500', $topListHtml);
    }

    public function test_top_large_expenses_summary_keeps_highest_expense_first(): void
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

        $this->createExpense($company, $branch, $category, 'Visual order expense 1000', 1000, 'cash', true, '2026-06-25');
        $this->createExpense($company, $branch, $category, 'Visual order expense 3000', 3000, 'cash', true, '2026-06-24');
        $this->createExpense($company, $branch, $category, 'Visual order expense 2000', 2000, 'cash', true, '2026-06-23');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
        ]));

        $response->assertOk();

        $topListHtml = $this->largeAmountTopListHtml($response->getContent());

        $firstPosition = strpos($topListHtml, 'Visual order expense 3000');
        $secondPosition = strpos($topListHtml, 'Visual order expense 2000');
        $thirdPosition = strpos($topListHtml, 'Visual order expense 1000');

        $this->assertNotFalse($firstPosition);
        $this->assertNotFalse($secondPosition);
        $this->assertNotFalse($thirdPosition);

        $this->assertTrue($firstPosition < $secondPosition);
        $this->assertTrue($secondPosition < $thirdPosition);
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
