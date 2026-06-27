<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountTopListTotalTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_large_expenses_summary_shows_total_for_displayed_top_five_only(): void
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

        $this->createExpense($company, $visibleBranch, $category, 'Top total large expense 6000', 6000, 'cash', true, '2026-06-26');
        $this->createExpense($company, $visibleBranch, $category, 'Top total large expense 5000', 5000, 'cash', true, '2026-06-25');
        $this->createExpense($company, $visibleBranch, $category, 'Top total large expense 4000', 4000, 'cash', true, '2026-06-24');
        $this->createExpense($company, $visibleBranch, $category, 'Top total large expense 3000', 3000, 'cash', true, '2026-06-23');
        $this->createExpense($company, $visibleBranch, $category, 'Top total large expense 2000', 2000, 'cash', true, '2026-06-22');

        $this->createExpense($company, $visibleBranch, $category, 'Sixth total large expense 1000', 1000, 'cash', true, '2026-06-21');
        $this->createExpense($company, $visibleBranch, $category, 'Small total expense 999', 999, 'cash', true, '2026-06-20');
        $this->createExpense($company, $hiddenBranch, $category, 'Hidden branch total large expense 7000', 7000, 'cash', true, '2026-06-19');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $visibleBranch->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $totalHtml = $this->largeAmountTopListTotalHtml($response->getContent());

        $this->assertStringContainsString('إجمالي أعلى 5 مصاريف كبيرة', $totalHtml);
        $this->assertStringContainsString('20,000.00 ريال', $totalHtml);

        $this->assertStringNotContainsString('21,000.00 ريال', $totalHtml);
        $this->assertStringNotContainsString('27,000.00 ريال', $totalHtml);
        $this->assertStringNotContainsString('27,999.00 ريال', $totalHtml);
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

    private function largeAmountTopListTotalHtml(string $content): string
    {
        $startNeedle = '<div data-testid="expense-large-amount-top-list-total"';
        $endNeedle = '</div>';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Large amount top list total section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Large amount top list total section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition + strlen($endNeedle));
    }
}
