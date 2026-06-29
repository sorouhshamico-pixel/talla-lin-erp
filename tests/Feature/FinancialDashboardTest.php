<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FinancialDashboardTest extends TestCase
{
    use RefreshDatabase;

    private int $revenueCategoryId;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-29 12:00:00'));

        $this->seed(InitialSetupSeeder::class);

        $this->revenueCategoryId = $this->resolveRevenueCategoryId();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_financial_dashboard_page_is_available(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('reports.financial-dashboard'));

        $response->assertOk();

        $response->assertSee('data-testid="financial-dashboard"', false);
        $response->assertSee('الداشبورد المالية');
        $response->assertSee('data-testid="financial-dashboard-current-month-revenues"', false);
        $response->assertSee('data-testid="financial-dashboard-current-month-expenses"', false);
        $response->assertSee('data-testid="financial-dashboard-current-month-net-profit"', false);
        $response->assertSee('data-testid="financial-dashboard-uncollected-revenues"', false);
        $response->assertSee('data-testid="financial-dashboard-unpaid-expenses"', false);
        $response->assertSee('data-testid="financial-dashboard-profit-loss-link"', false);
    }

    public function test_financial_dashboard_calculates_current_month_and_outstanding_totals(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();
        DB::table('revenues')->delete();

        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $this->insertRevenue([
            'code' => 'REV-CURRENT-COLLECTED-001',
            'description' => 'Current month collected revenue',
            'amount' => 10000,
            'tax_amount' => 1500,
            'branch_id' => $branchId,
            'revenue_date' => '2026-06-10',
            'is_collected' => true,
        ]);

        $this->insertRevenue([
            'code' => 'REV-CURRENT-UNCOLLECTED-001',
            'description' => 'Current month uncollected revenue',
            'amount' => 4000,
            'tax_amount' => 600,
            'branch_id' => $branchId,
            'revenue_date' => '2026-06-20',
            'is_collected' => false,
        ]);

        $this->insertRevenue([
            'code' => 'REV-OUTSIDE-MONTH-001',
            'description' => 'Outside current month revenue',
            'amount' => 50000,
            'tax_amount' => 7500,
            'branch_id' => $branchId,
            'revenue_date' => '2026-05-20',
            'is_collected' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-CURRENT-PAID-001',
            'description' => 'Current month paid expense',
            'amount' => 3000,
            'tax_amount' => 450,
            'branch_id' => $branchId,
            'expense_date' => '2026-06-12',
            'is_paid' => true,
        ]);

        $this->insertExpense([
            'code' => 'EXP-CURRENT-UNPAID-001',
            'description' => 'Current month unpaid expense',
            'amount' => 1000,
            'tax_amount' => 150,
            'branch_id' => $branchId,
            'expense_date' => '2026-06-22',
            'is_paid' => false,
        ]);

        $this->insertExpense([
            'code' => 'EXP-OUTSIDE-MONTH-001',
            'description' => 'Outside current month expense',
            'amount' => 60000,
            'tax_amount' => 9000,
            'branch_id' => $branchId,
            'expense_date' => '2026-05-22',
            'is_paid' => true,
        ]);

        $response = $this->get(route('reports.financial-dashboard'));

        $response->assertOk();

        $response->assertSee('14,000.00 ريال');
        $response->assertSee('4,000.00 ريال');
        $response->assertSee('10,000.00 ريال');
        $response->assertSee('1,000.00 ريال');

        $response->assertDontSee('50,000.00 ريال');
        $response->assertDontSee('60,000.00 ريال');
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertRevenue(array $overrides): void
    {
        $now = now();

        DB::table('revenues')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'branch_id' => $overrides['branch_id'],
            'revenue_category_id' => $this->revenueCategoryId,
            'code' => $overrides['code'],
            'revenue_date' => $overrides['revenue_date'],
            'description' => $overrides['description'],
            'amount' => $overrides['amount'],
            'tax_amount' => $overrides['tax_amount'],
            'collection_method' => 'cash',
            'is_collected' => $overrides['is_collected'],
            'reference_number' => null,
            'notes' => null,
            'archived_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function insertExpense(array $overrides): void
    {
        $now = now();

        DB::table('expenses')->insert([
            'company_id' => DB::table('companies')->value('id'),
            'branch_id' => $overrides['branch_id'],
            'expense_category_id' => DB::table('expense_categories')->value('id'),
            'user_id' => DB::table('users')->value('id'),
            'code' => $overrides['code'],
            'description' => $overrides['description'],
            'amount' => $overrides['amount'],
            'tax_amount' => $overrides['tax_amount'],
            'payment_method' => 'cash',
            'expense_date' => $overrides['expense_date'],
            'reference_number' => null,
            'notes' => null,
            'is_paid' => $overrides['is_paid'],
            'attachment_path' => null,
            'attachment_original_name' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function resolveRevenueCategoryId(): int
    {
        $categoryId = DB::table('revenues')
            ->whereNotNull('revenue_category_id')
            ->value('revenue_category_id');

        if ($categoryId !== null) {
            return (int) $categoryId;
        }

        if (Schema::hasTable('revenue_categories')) {
            $categoryId = DB::table('revenue_categories')->value('id');

            if ($categoryId !== null) {
                return (int) $categoryId;
            }

            $columns = Schema::getColumnListing('revenue_categories');
            $now = now();

            $data = [
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (in_array('company_id', $columns, true)) {
                $data['company_id'] = DB::table('companies')->value('id');
            }

            if (in_array('name', $columns, true)) {
                $data['name'] = 'Test Revenue Category';
            }

            if (in_array('name_ar', $columns, true)) {
                $data['name_ar'] = 'تصنيف إيراد اختباري';
            }

            if (in_array('name_en', $columns, true)) {
                $data['name_en'] = 'Test Revenue Category';
            }

            if (in_array('slug', $columns, true)) {
                $data['slug'] = 'test-revenue-category';
            }

            if (in_array('description', $columns, true)) {
                $data['description'] = 'Test revenue category';
            }

            if (in_array('is_active', $columns, true)) {
                $data['is_active'] = true;
            }

            return (int) DB::table('revenue_categories')->insertGetId($data);
        }

        $this->fail('Cannot resolve revenue_category_id for FinancialDashboardTest.');

        return 0;
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
