<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExpenseLargePaidExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_large_paid_export_route_is_available(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.export-large-paid'));

        $response->assertOk();

        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->assertHeader('Content-Disposition');
    }

    public function test_large_paid_summary_export_button_appears_in_expenses_index(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index'));

        $response->assertOk();

        $response->assertSee('data-testid="expense-large-paid-summary-export"', false);
        $response->assertSee('تصدير CSV للمصاريف الكبيرة المدفوعة');
        $response->assertSee(route('expenses.export-large-paid'), false);
    }

    public function test_large_paid_export_contains_only_large_paid_expenses_with_current_filters(): void
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

        $response = $this->get(route('expenses.export-large-paid', [
            'payment_method' => 'cash',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('EXP-LP-001', $content);
        $this->assertStringContainsString('EXP-LP-002', $content);

        $this->assertStringNotContainsString('EXP-LU-001', $content);
        $this->assertStringNotContainsString('EXP-SP-001', $content);
        $this->assertStringNotContainsString('EXP-LP-BANK-001', $content);

        $this->assertStringContainsString('مدفوع', $content);
        $this->assertStringNotContainsString('غير مدفوع', $content);
    }

    public function test_large_paid_summary_export_link_preserves_filters_and_overrides_page(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('expenses.index', [
            'payment_method' => 'cash',
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
            'page' => 3,
        ]));

        $response->assertOk();

        $content = $response->getContent();

        preg_match(
            '/<a(?=[^>]*data-testid="expense-large-paid-summary-export")[^>]*href="([^"]+)"/',
            $content,
            $matches
        );

        $this->assertNotEmpty($matches, 'Large paid summary export link was not found.');

        $href = html_entity_decode($matches[1]);

        $this->assertStringContainsString('payment_method=cash', $href);
        $this->assertStringContainsString('from_date=2026-01-01', $href);
        $this->assertStringContainsString('to_date=2026-01-31', $href);
        $this->assertStringContainsString('large_amount=1', $href);
        $this->assertStringContainsString('payment_status=paid', $href);

        $this->assertStringNotContainsString('page=3', $href);
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
