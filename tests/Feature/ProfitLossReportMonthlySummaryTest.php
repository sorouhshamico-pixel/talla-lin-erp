<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfitLossReportMonthlySummaryTest extends TestCase
{
    use RefreshDatabase;

    private int $revenueCategoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->revenueCategoryId = $this->resolveRevenueCategoryId();
    }

    public function test_profit_loss_report_displays_monthly_summary(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();
        DB::table('revenues')->delete();

        [$branchOne, $branchTwo] = $this->twoBranchIds();

        $this->insertRevenue([
            'code' => 'REV-JAN-001',
            'description' => 'January revenue',
            'amount' => 10000,
            'tax_amount' => 1500,
            'branch_id' => $branchOne,
            'revenue_date' => '2026-01-10',
        ]);

        $this->insertRevenue([
            'code' => 'REV-FEB-001',
            'description' => 'February revenue',
            'amount' => 6000,
            'tax_amount' => 900,
            'branch_id' => $branchOne,
            'revenue_date' => '2026-02-10',
        ]);

        $this->insertExpense([
            'code' => 'EXP-JAN-001',
            'description' => 'January expense',
            'amount' => 3000,
            'tax_amount' => 450,
            'branch_id' => $branchOne,
            'expense_date' => '2026-01-15',
        ]);

        $this->insertExpense([
            'code' => 'EXP-FEB-001',
            'description' => 'February expense',
            'amount' => 8000,
            'tax_amount' => 1200,
            'branch_id' => $branchOne,
            'expense_date' => '2026-02-15',
        ]);

        $this->insertRevenue([
            'code' => 'REV-OTHER-BRANCH-001',
            'description' => 'Other branch revenue',
            'amount' => 50000,
            'tax_amount' => 7500,
            'branch_id' => $branchTwo,
            'revenue_date' => '2026-01-10',
        ]);

        $response = $this->get(route('reports.profit-loss', [
            'branch_id' => $branchOne,
            'from_date' => '2026-01-01',
            'to_date' => '2026-02-28',
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="profit-loss-monthly-summary"', false);
        $response->assertSee('الملخص الشهري');

        $response->assertSee('2026-01');
        $response->assertSee('10,000.00 ريال');
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('7,000.00 ريال');

        $response->assertSee('2026-02');
        $response->assertSee('6,000.00 ريال');
        $response->assertSee('8,000.00 ريال');
        $response->assertSee('-2,000.00 ريال');

        $response->assertDontSee('50,000.00 ريال');
    }

    public function test_profit_loss_report_displays_empty_monthly_summary_message(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();
        DB::table('revenues')->delete();

        $response = $this->get(route('reports.profit-loss'));

        $response->assertOk();

        $response->assertSee('data-testid="profit-loss-monthly-summary"', false);
        $response->assertSee('لا توجد بيانات شهرية ضمن الفلاتر الحالية.');
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
            'is_collected' => true,
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
            'is_paid' => true,
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

        $this->fail('Cannot resolve revenue_category_id for ProfitLossReportMonthlySummaryTest.');

        return 0;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function twoBranchIds(): array
    {
        $ids = DB::table('branches')
            ->orderBy('id')
            ->limit(2)
            ->pluck('id')
            ->all();

        $this->assertCount(2, $ids, 'InitialSetupSeeder must create at least two branches.');

        return [(int) $ids[0], (int) $ids[1]];
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
