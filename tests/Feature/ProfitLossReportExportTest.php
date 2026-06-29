<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfitLossReportExportTest extends TestCase
{
    use RefreshDatabase;

    private int $revenueCategoryId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);

        $this->revenueCategoryId = $this->resolveRevenueCategoryId();
    }

    public function test_profit_loss_report_has_export_button_that_preserves_filters(): void
    {
        $this->actingAsOwner();

        $branchId = DB::table('branches')->orderBy('id')->value('id');

        $response = $this->get(route('reports.profit-loss', [
            'branch_id' => $branchId,
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ]));

        $response->assertOk();

        $response->assertSee('data-testid="profit-loss-export"', false);
        $response->assertSee('reports/profit-loss/export', false);
        $response->assertSee('branch_id=' . $branchId, false);
        $response->assertSee('from_date=2026-01-01', false);
        $response->assertSee('to_date=2026-01-31', false);
    }

    public function test_profit_loss_report_export_calculates_filtered_totals(): void
    {
        $this->actingAsOwner();

        DB::table('expenses')->delete();
        DB::table('revenues')->delete();

        [$branchOne, $branchTwo] = $this->twoBranchIds();

        $this->insertRevenue([
            'code' => 'REV-EXPORT-MATCH-001',
            'description' => 'Revenue export match',
            'amount' => 7000,
            'tax_amount' => 1050,
            'branch_id' => $branchOne,
            'revenue_date' => '2026-01-10',
        ]);

        $this->insertRevenue([
            'code' => 'REV-EXPORT-OUT-001',
            'description' => 'Revenue export out',
            'amount' => 11000,
            'tax_amount' => 1650,
            'branch_id' => $branchTwo,
            'revenue_date' => '2026-01-10',
        ]);

        $this->insertExpense([
            'code' => 'EXP-EXPORT-MATCH-001',
            'description' => 'Expense export match',
            'amount' => 2500,
            'tax_amount' => 375,
            'branch_id' => $branchOne,
            'expense_date' => '2026-01-15',
        ]);

        $this->insertExpense([
            'code' => 'EXP-EXPORT-OUT-001',
            'description' => 'Expense export out',
            'amount' => 4000,
            'tax_amount' => 600,
            'branch_id' => $branchTwo,
            'expense_date' => '2026-01-15',
        ]);

        $response = $this->get(route('reports.profit-loss.export', [
            'branch_id' => $branchOne,
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
        ]));

        $response->assertOk();

        $contentDisposition = $response->headers->get('Content-Disposition') ?? '';

        $this->assertStringContainsString('profit-loss-report-', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);

        $content = $response->streamedContent();

        $this->assertStringContainsString('البند', $content);
        $this->assertStringContainsString('القيمة', $content);

        $this->assertStringContainsString('إجمالي الإيرادات', $content);
        $this->assertStringContainsString('7000.00', $content);

        $this->assertStringContainsString('إجمالي المصروفات', $content);
        $this->assertStringContainsString('2500.00', $content);

        $this->assertStringContainsString('صافي الربح / الخسارة', $content);
        $this->assertStringContainsString('4500.00', $content);

        $this->assertStringContainsString('ضريبة الإيرادات', $content);
        $this->assertStringContainsString('1050.00', $content);

        $this->assertStringContainsString('ضريبة المصروفات', $content);
        $this->assertStringContainsString('375.00', $content);

        $this->assertStringContainsString('فرق الضريبة', $content);
        $this->assertStringContainsString('675.00', $content);

        $this->assertStringNotContainsString('11000.00', $content);
        $this->assertStringNotContainsString('4000.00', $content);
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

        $this->fail('Cannot resolve revenue_category_id for ProfitLossReportExportTest.');

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
