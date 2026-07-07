<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashFlowDashboardDateRangePresetTest extends TestCase
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

    public function test_cash_flow_dashboard_displays_date_range_preset_links(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-date-range-presets"', false);
        $response->assertSee('data-testid="cash-flow-date-range-preset-current-month"', false);
        $response->assertSee('data-testid="cash-flow-date-range-preset-next-30-days"', false);
        $response->assertSee('data-testid="cash-flow-date-range-preset-next-month"', false);
        $response->assertSee('data-testid="cash-flow-date-range-preset-until-today"', false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ])), false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'date_from' => '2026-07-06',
            'date_to' => '2026-08-05',
        ])), false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ])), false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'date_to' => '2026-07-06',
        ])), false);
    }

    public function test_cash_flow_dashboard_date_range_preset_links_preserve_branch(): void
    {
        $user = User::query()->firstOrFail();

        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));

        $response->assertOk();

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ])), false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-06',
            'date_to' => '2026-08-05',
        ])), false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'branch_id' => $branchId,
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ])), false);

        $response->assertSee(e(route('reports.cash-flow-dashboard.index', [
            'branch_id' => $branchId,
            'date_to' => '2026-07-06',
        ])), false);
    }
}
