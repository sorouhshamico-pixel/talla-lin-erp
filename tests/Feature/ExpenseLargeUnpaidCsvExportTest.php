<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeUnpaidCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_index_shows_large_unpaid_summary_csv_export_button_preserving_filters(): void
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
        $response->assertSee('تصدير CSV للمصاريف الكبيرة غير المدفوعة');
        $response->assertSee('expense-large-unpaid-summary-export', false);

        $html = html_entity_decode($response->getContent());

        $this->assertStringContainsString('branch_id=' . $branch->id, $html);
        $this->assertStringContainsString('large_amount=1', $html);
        $this->assertStringContainsString('payment_status=unpaid', $html);
    }

    public function test_large_unpaid_csv_export_exports_only_large_unpaid_expenses_respecting_filters(): void
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

        $this->createExpense($company, $visibleBranch, $category, 'CSV visible large unpaid expense 1800', 1800, 'cash', false, '2026-06-25');
        $this->createExpense($company, $visibleBranch, $category, 'CSV visible large unpaid expense 1200', 1200, 'cash', false, '2026-06-24');

        $this->createExpense($company, $visibleBranch, $category, 'CSV hidden large paid expense 5000', 5000, 'cash', true, '2026-06-23');
        $this->createExpense($company, $visibleBranch, $category, 'CSV hidden small unpaid expense 999', 999, 'cash', false, '2026-06-22');
        $this->createExpense($company, $hiddenBranch, $category, 'CSV hidden branch large unpaid expense 4000', 4000, 'cash', false, '2026-06-21');

        $response = $this->actingAs($user)->get(route('expenses.export-large-unpaid', [
            'branch_id' => $visibleBranch->id,
            'payment_method' => 'cash',
            'large_amount' => '1',
            'payment_status' => 'unpaid',
        ]));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('CSV visible large unpaid expense 1800', $csv);
        $this->assertStringContainsString('CSV visible large unpaid expense 1200', $csv);

        $this->assertStringNotContainsString('CSV hidden large paid expense 5000', $csv);
        $this->assertStringNotContainsString('CSV hidden small unpaid expense 999', $csv);
        $this->assertStringNotContainsString('CSV hidden branch large unpaid expense 4000', $csv);
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
