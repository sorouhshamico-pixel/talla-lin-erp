<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_large_amount_alert_respecting_current_filters(): void
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

        $this->createExpense($company, $visibleBranch, $category, 'Large filtered expense', 1250, 'cash', 'paid', '2026-06-10');
        $this->createExpense($company, $visibleBranch, $category, 'Small filtered expense', 999, 'cash', 'paid', '2026-06-10');
        $this->createExpense($company, $hiddenBranch, $category, 'Large hidden branch expense', 3000, 'cash', 'paid', '2026-06-10');
        $this->createExpense($company, $visibleBranch, $category, 'Large hidden payment method expense', 2200, 'bank_transfer', 'paid', '2026-06-10');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'branch_id' => $visibleBranch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ]));

        $response->assertOk();

        $alertHtml = $this->largeAmountAlertHtml($response->getContent());

        $this->assertStringContainsString('تنبيه المصاريف الكبيرة', $alertHtml);
        $this->assertStringContainsString('عدد المصاريف الكبيرة', $alertHtml);
        $this->assertStringContainsString('إجمالي قيمتها', $alertHtml);
        $this->assertStringContainsString('أعلى مصروف ضمن الفلاتر الحالية', $alertHtml);
        $this->assertStringContainsString('عرض المصاريف الكبيرة', $alertHtml);
        $this->assertStringContainsString('1,250.00 ريال', $alertHtml);
        $this->assertStringContainsString('Large filtered expense', $alertHtml);
        $this->assertStringNotContainsString('3,000.00 ريال', $alertHtml);
        $this->assertStringNotContainsString('2,200.00 ريال', $alertHtml);
    }

    public function test_large_amount_quick_filter_link_preserves_current_filters(): void
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

        $this->createExpense($company, $branch, $category, 'Large quick filter expense', 1500, 'cash', 'unpaid', '2026-06-12');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'attachment_status' => 'without_attachment',
        ]));

        $response->assertOk();

        $expectedUrl = route('expenses.index', [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-30',
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'attachment_status' => 'without_attachment',
            'large_amount' => '1',
        ]);

        $alertHtml = $this->largeAmountAlertHtml($response->getContent());

        $this->assertStringContainsString('عرض المصاريف الكبيرة', $alertHtml);
        $this->assertStringContainsString(e($expectedUrl), $alertHtml);
    }

    public function test_large_amount_quick_filter_filters_expenses_list(): void
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

        $this->createExpense($company, $branch, $category, 'Visible large amount expense', 1000, 'cash', 'paid', '2026-06-15');
        $this->createExpense($company, $branch, $category, 'Hidden small amount expense', 999, 'cash', 'paid', '2026-06-15');

        $response = $this->actingAs($user)->get(route('expenses.index', [
            'branch_id' => $branch->id,
            'large_amount' => '1',
        ]));

        $response->assertOk();
        $response->assertSee('Visible large amount expense');
        $response->assertDontSee('Hidden small amount expense');
    }

    private function createExpense(
        Company $company,
        Branch $branch,
        ExpenseCategory $category,
        string $description,
        float $amount,
        string $paymentMethod,
        string $paymentStatus,
        string $expenseDate
    ): Expense {
        return Expense::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => $description,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'expense_date' => $expenseDate,
        ]);
    }

    private function largeAmountAlertHtml(string $content): string
    {
        $startNeedle = '<div class="card" data-testid="expense-large-amount-alert"';
        $endNeedle = '<div class="card" data-testid="expense-unpaid-alert"';

        $startPosition = strpos($content, $startNeedle);

        $this->assertNotFalse($startPosition, 'Large amount alert section was not found.');

        $endPosition = strpos($content, $endNeedle, (int) $startPosition);

        $this->assertNotFalse($endPosition, 'Large amount alert section end was not found.');

        return substr($content, (int) $startPosition, (int) $endPosition - (int) $startPosition);
    }
}
