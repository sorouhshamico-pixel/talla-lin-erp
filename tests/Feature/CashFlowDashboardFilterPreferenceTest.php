<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ReportFilterPreferenceService;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashFlowDashboardFilterPreferenceTest extends TestCase
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

    public function test_cash_flow_dashboard_saves_submitted_filters_as_user_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);

        $this->assertSame([
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ], app(ReportFilterPreferenceService::class)->get($user, 'cash-flow-dashboard'));
    }

    public function test_cash_flow_dashboard_reuses_saved_filter_preferences_when_no_filters_are_submitted(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        app(ReportFilterPreferenceService::class)->save($user, 'cash-flow-dashboard', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('value="2026-07-01"', false);
        $response->assertSee('value="2026-07-31"', false);
        $response->assertSee(e(route('reports.cash-flow-dashboard.print', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ])), false);
        $response->assertSee(e(route('reports.cash-flow-dashboard.export', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ])), false);
    }

    public function test_cash_flow_dashboard_reset_clears_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');
        $service = app(ReportFilterPreferenceService::class);

        $service->save($user, 'cash-flow-dashboard', [
            'branch_id' => $branchId,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]);

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index', [
            'reset_filters' => 1,
        ]));

        $response->assertOk();

        $this->assertSame([], $service->get($user, 'cash-flow-dashboard'));

        $response->assertSee('value="" data-testid="cash-flow-date-from-input"', false);
        $response->assertSee('value="" data-testid="cash-flow-date-to-input"', false);
        $response->assertSee(e(route('reports.cash-flow-dashboard.print')), false);
        $response->assertSee(e(route('reports.cash-flow-dashboard.export')), false);
    }

    public function test_cash_flow_print_and_export_can_use_saved_filter_preferences(): void
    {
        $user = User::query()->firstOrFail();
        $branch = DB::table('branches')->orderBy('id')->first();

        app(ReportFilterPreferenceService::class)->save($user, 'cash-flow-dashboard', [
            'branch_id' => $branch->id,
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]);

        $printResponse = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.print'));

        $printResponse->assertOk();
        $printResponse->assertSee('2026-07-01');
        $printResponse->assertSee('2026-07-31');
        $printResponse->assertSee($branch->name);

        $exportResponse = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.export'));

        $exportResponse->assertOk();

        $content = $exportResponse->streamedContent();

        $this->assertStringContainsString('2026-07-01', $content);
        $this->assertStringContainsString('2026-07-31', $content);
        $this->assertStringContainsString($branch->name, $content);
    }
}
