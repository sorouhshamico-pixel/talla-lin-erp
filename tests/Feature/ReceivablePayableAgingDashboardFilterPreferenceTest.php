<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportFilterPreferenceService;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceivablePayableAgingDashboardFilterPreferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-07-06 10:00:00');

        $this->seed(InitialSetupSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_aging_dashboard_saves_submitted_filters_as_user_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-07-31"', false);

        $this->assertSame([
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ], app(ReportFilterPreferenceService::class)->get($user, 'receivable-payable-aging-dashboard'));
    }

    public function test_aging_dashboard_reuses_saved_filter_preferences_when_no_filters_are_submitted(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        app(ReportFilterPreferenceService::class)->save($user, 'receivable-payable-aging-dashboard', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ]);

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index'));

        $response->assertOk();
        $response->assertSee('value="2026-07-31"', false);
        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.print', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ])), false);
        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.export', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ])), false);
    }

    public function test_aging_dashboard_reset_clears_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'receivable-payable-aging-dashboard', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ]);

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertSame([], $service->get($user, 'receivable-payable-aging-dashboard'));

        $response->assertSee('value="2026-07-06"', false);
        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.print')), false);
        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.export')), false);
    }

    public function test_aging_dashboard_print_and_export_can_use_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $branch = DB::table('branches')->orderBy('id')->first();

        app(ReportFilterPreferenceService::class)->save($user, 'receivable-payable-aging-dashboard', [
            'branch_id' => $branch->id,
            'as_of_date' => '2026-07-31',
        ]);

        $printResponse = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.print'));

        $printResponse->assertOk();
        $printResponse->assertSee('2026-07-31');
        $printResponse->assertSee($branch->name);

        $exportResponse = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString('2026-07-31', $content);
        $this->assertStringContainsString($branch->name, $content);
    }

    public function test_aging_dashboard_saved_branch_preference_filters_totals(): void
    {
        $user = User::query()->firstOrFail();

        $firstBranchId = (int) DB::table('branches')->orderBy('id')->value('id');
        $secondBranchId = (int) DB::table('branches')->where('id', '!=', $firstBranchId)->orderBy('id')->value('id');

        $this->assertGreaterThan(0, $secondBranchId);

        DB::table('sales_invoices')->update(['branch_id' => $secondBranchId]);
        DB::table('purchase_invoices')->update(['branch_id' => $secondBranchId]);

        $this->insertInvoiceRow('sales_invoices', $firstBranchId, 3000, '2026-06-15', 'SALES-PREF-1');
        $this->insertInvoiceRow('sales_invoices', $secondBranchId, 9000, '2026-06-15', 'SALES-PREF-2');

        $this->insertInvoiceRow('purchase_invoices', $firstBranchId, 1000, '2026-06-15', 'PURCHASE-PREF-1');
        $this->insertInvoiceRow('purchase_invoices', $secondBranchId, 7000, '2026-06-15', 'PURCHASE-PREF-2');

        app(ReportFilterPreferenceService::class)->save($user, 'receivable-payable-aging-dashboard', [
            'branch_id' => $firstBranchId,
            'as_of_date' => '2026-07-31',
        ]);

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index'));

        $response->assertOk();
        $response->assertSee('3,000.00 ريال');
        $response->assertSee('1,000.00 ريال');
        $response->assertSee('2,000.00 ريال');
        $response->assertDontSee('9,000.00 ريال');
        $response->assertDontSee('7,000.00 ريال');
    }

    private function insertInvoiceRow(string $table, int $branchId, float $remainingAmount, string $dueAt, string $numberPrefix): void
    {
        $source = DB::table($table)->orderBy('id')->first();

        $this->assertNotNull($source, "A seeded {$table} row is required for this test.");

        $row = (array) $source;

        unset($row['id']);

        $columns = Schema::getColumnListing($table);
        $row = array_intersect_key($row, array_flip($columns));

        if (in_array('invoice_number', $columns, true)) {
            $row['invoice_number'] = $numberPrefix . '-' . uniqid();
        }

        if (in_array('reference_number', $columns, true)) {
            $row['reference_number'] = $numberPrefix . '-REF-' . uniqid();
        }

        if (in_array('branch_id', $columns, true)) {
            $row['branch_id'] = $branchId;
        }

        foreach (['remaining_amount', 'total_amount', 'grand_total', 'net_amount', 'amount'] as $amountColumn) {
            if (in_array($amountColumn, $columns, true)) {
                $row[$amountColumn] = $remainingAmount;
            }
        }

        foreach (['paid_amount', 'amount_paid'] as $paidColumn) {
            if (in_array($paidColumn, $columns, true)) {
                $row[$paidColumn] = 0;
            }
        }

        if (in_array('due_at', $columns, true)) {
            $row['due_at'] = $dueAt;
        }

        foreach (['invoice_date', 'issued_at', 'invoice_at'] as $dateColumn) {
            if (in_array($dateColumn, $columns, true)) {
                $row[$dateColumn] = '2026-06-01';
            }
        }

        if (in_array('created_at', $columns, true)) {
            $row['created_at'] = now();
        }

        if (in_array('updated_at', $columns, true)) {
            $row['updated_at'] = now();
        }

        DB::table($table)->insert($row);
    }
}
