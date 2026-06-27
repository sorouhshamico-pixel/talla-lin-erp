<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountTopListCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_top_large_expenses_csv_export_button(): void
    {
        $this->seed();

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('تصدير أعلى 5 CSV');
        $response->assertSee('expense-large-amount-top-list-export', false);
    }

    public function test_top_large_expenses_csv_export_respects_current_filters_and_exports_top_five_only(): void
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

        $this->createExpense($company, $visibleBranch, $category, 'CSV top large expense 6000', 6000, 'cash', true, '2026-06-26');
        $this->createExpense($company, $visibleBranch, $category, 'CSV top large expense 5000', 5000, 'cash', true, '2026-06-25');
        $this->createExpense($company, $visibleBranch, $category, 'CSV top large expense 4000', 4000, 'cash', true, '2026-06-24');
        $this->createExpense($company, $visibleBranch, $category, 'CSV top large expense 3000', 3000, 'cash', true, '2026-06-23');
        $this->createExpense($company, $visibleBranch, $category, 'CSV top large expense 2000', 2000, 'cash', true, '2026-06-22');

        $this->createExpense($company, $visibleBranch, $category, 'CSV sixth large expense 1000', 1000, 'cash', true, '2026-06-21');
        $this->createExpense($company, $visibleBranch, $category, 'CSV small expense 999', 999, 'cash', true, '2026-06-20');
        $this->createExpense($company, $hiddenBranch, $category, 'CSV hidden branch large expense 7000', 7000, 'cash', true, '2026-06-19');

        $response = $this->actingAs($user)->get(route('expenses.export-top-large', [
            'branch_id' => $visibleBranch->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('CSV top large expense 6000', $csv);
        $this->assertStringContainsString('CSV top large expense 5000', $csv);
        $this->assertStringContainsString('CSV top large expense 4000', $csv);
        $this->assertStringContainsString('CSV top large expense 3000', $csv);
        $this->assertStringContainsString('CSV top large expense 2000', $csv);

        $this->assertStringNotContainsString('CSV sixth large expense 1000', $csv);
        $this->assertStringNotContainsString('CSV small expense 999', $csv);
        $this->assertStringNotContainsString('CSV hidden branch large expense 7000', $csv);
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
