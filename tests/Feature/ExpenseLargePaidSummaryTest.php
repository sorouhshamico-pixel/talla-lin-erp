<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseLargePaidSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_expenses_index_shows_large_paid_summary_card(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-large-paid-summary"', false);
        $response->assertSee('ملخص المصاريف الكبيرة المدفوعة');
        $response->assertSee('data-testid="expense-large-paid-summary-count"', false);
        $response->assertSee('data-testid="expense-large-paid-summary-total"', false);
    }

    public function test_large_paid_summary_appears_after_large_unpaid_summary_before_top_large_list(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $content = $response->getContent();

        $largeUnpaidSummaryPosition = strpos($content, 'data-testid="expense-large-unpaid-summary"');
        $largePaidSummaryPosition = strpos($content, 'data-testid="expense-large-paid-summary"');
        $topLargeListPosition = strpos($content, 'data-testid="expense-large-amount-top-list"');

        $this->assertNotFalse($largeUnpaidSummaryPosition);
        $this->assertNotFalse($largePaidSummaryPosition);
        $this->assertNotFalse($topLargeListPosition);

        $this->assertLessThan($largePaidSummaryPosition, $largeUnpaidSummaryPosition);
        $this->assertLessThan($topLargeListPosition, $largePaidSummaryPosition);
    }

    public function test_large_paid_summary_counts_only_large_paid_expenses_with_current_filters(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();

        $this->insertExpense([
            'code' => 'EXP-LP-001',
            'description' => 'Large paid cash expense',
            'amount' => 1500,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-002',
            'description' => 'Another large paid cash expense',
            'amount' => 2500,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LU-001',
            'description' => 'Large unpaid cash expense',
            'amount' => 3000,
            'payment_method' => 'cash',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-SP-001',
            'description' => 'Small paid cash expense',
            'amount' => 500,
            'payment_method' => 'cash',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-LP-BANK-001',
            'description' => 'Large paid bank expense',
            'amount' => 6000,
            'payment_method' => 'bank_transfer',
            'is_paid' => true,
        ]);

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/data-testid="expense-large-paid-summary-count"[^>]*>\s*2\s*</',
            $content,
            $countMatches
        );

        $this->assertNotEmpty($countMatches, 'Large paid summary count should be 2.');

        $response->assertSee('4,000.00 ريال');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertExpense(array $overrides): void
    {
        $now = now();

        DB::table('expenses')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'branch_id' => DB::table('branches')->value('id'),
            'expense_category_id' => DB::table('expense_categories')->value('id'),
            'user_id' => DB::table('users')->value('id'),
            'code' => $overrides['code'],
            'description' => $overrides['description'],
            'amount' => $overrides['amount'],
            'tax_amount' => 0,
            'payment_method' => $overrides['payment_method'],
            'expense_date' => '2026-01-10',
            'reference_number' => null,
            'notes' => null,
            'is_paid' => $overrides['is_paid'],
            'attachment_path' => null,
            'attachment_original_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
