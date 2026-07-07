<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashFlowDashboardTest extends TestCase
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

    public function test_cash_flow_dashboard_page_loads(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.cash-flow-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-dashboard"', false);
        $response->assertSee('لوحة التدفق النقدي المتوقع');
        $response->assertSee('تاريخ التقرير: 2026-07-06');
        $response->assertSee('data-testid="cash-flow-inflow-summary"', false);
        $response->assertSee('data-testid="cash-flow-outflow-summary"', false);
        $response->assertSee('data-testid="cash-flow-net-summary"', false);
        $response->assertSee('التدفقات الداخلة المتوقعة');
        $response->assertSee('التدفقات الخارجة المتوقعة');
        $response->assertSee('صافي التدفق النقدي المتوقع');
        $response->assertSee('data-testid="cash-flow-customer-aging-link"', false);
        $response->assertSee('data-testid="cash-flow-supplier-aging-link"', false);
        $response->assertSee('data-testid="cash-flow-aging-dashboard-link"', false);
    }

    public function test_reports_index_displays_cash_flow_dashboard_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="cash-flow-dashboard-link"', false);
        $response->assertSee('لوحة التدفق النقدي المتوقع');
        $response->assertSee(route('reports.cash-flow-dashboard.index'), false);
    }
}
