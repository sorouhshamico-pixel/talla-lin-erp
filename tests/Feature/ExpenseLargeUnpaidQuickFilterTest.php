<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeUnpaidQuickFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_large_unpaid_quick_filter_link_preserving_current_filters(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();
        $company = Company::query()->firstOrFail();

        $branch = Branch::query()
            ->where('company_id', $company->id)
            ->orderBy('id')
            ->firstOrFail();

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
        ]));

        $response->assertOk();
        $response->assertSee('عرض المصاريف الكبيرة غير المدفوعة');
        $response->assertSee('expense-large-unpaid-quick-filter', false);

        $html = html_entity_decode($response->getContent());

        $this->assertStringContainsString('branch_id=' . $branch->id, $html);
        $this->assertStringContainsString('large_amount=1', $html);
        $this->assertStringContainsString('payment_status=unpaid', $html);
    }

    public function test_large_unpaid_quick_filter_query_shows_only_large_unpaid_expenses(): void
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

        $this->createExpense($company, $visibleBranch, $category, 'Visible large unpaid quick filter expense', 1800, 'cash', false, '2026-06-25');
        $this->createExpense($company, $visibleBranch, $category, 'Hidden large paid quick filter expense', 2500, 'cash', true, '2026-06-24');
        $this->createExpense($company, $visibleBranch, $category, 'Hidden small unpaid quick filter expense', 999, 'cash', false, '2026-06-23');
        $this->createExpense($company, $hiddenBranch, $category, 'Hidden branch large unpaid quick filter expense', 4000, 'cash', false, '2026-06-22');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $visibleBranch->id,
            'large_amount' => '1',
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('فلتر المصاريف الكبيرة غير المدفوعة مفعّل', $content);
        $this->assertStringContainsString('Visible large unpaid quick filter expense', $content);

        $this->assertStringNotContainsString('Hidden large paid quick filter expense', $content);
        $this->assertStringNotContainsString('Hidden small unpaid quick filter expense', $content);
        $this->assertStringNotContainsString('Hidden branch large unpaid quick filter expense', $content);
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
}
