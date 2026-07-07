<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceivablePayableAgingDashboardReportDatePresetTest extends TestCase
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

    public function test_aging_dashboard_displays_report_date_preset_links(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="aging-dashboard-report-date-presets"', false);
        $response->assertSee('data-testid="aging-dashboard-preset-today"', false);
        $response->assertSee('data-testid="aging-dashboard-preset-current-month-end"', false);
        $response->assertSee('data-testid="aging-dashboard-preset-next-month-end"', false);
        $response->assertSee('data-testid="aging-dashboard-preset-current-quarter-end"', false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'as_of_date' => '2026-07-06',
        ])), false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'as_of_date' => '2026-07-31',
        ])), false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'as_of_date' => '2026-08-31',
        ])), false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'as_of_date' => '2026-09-30',
        ])), false);
    }

    public function test_aging_dashboard_report_date_presets_preserve_branch(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ]));

        $response->assertOk();

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-06',
        ])), false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-31',
        ])), false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-08-31',
        ])), false);

        $response->assertSee(e(route('reports.receivable-payable-aging-dashboard.index', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-09-30',
        ])), false);
    }
}
