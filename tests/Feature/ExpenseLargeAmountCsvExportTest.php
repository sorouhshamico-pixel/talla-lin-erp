<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseLargeAmountCsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_expenses_csv_export_respects_large_amount_filter(): void
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
            'description' => 'CSV visible large amount expense',
            'amount' => 1000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'expense_date' => '2026-06-21',
        ]);

        Expense::query()->create([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $category->id,
            'description' => 'CSV hidden small amount expense',
            'amount' => 999,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'expense_date' => '2026-06-21',
        ]);

        $response = $this->actingAs($user)->get(route('expenses.export', [
            'branch_id' => $branch->id,
            'large_amount' => '1',
        ]));

        $response->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('CSV visible large amount expense', $csv);
        $this->assertStringNotContainsString('CSV hidden small amount expense', $csv);
    }
}
