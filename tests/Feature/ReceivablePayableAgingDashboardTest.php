<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivablePayableAgingDashboardTest extends TestCase
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

    public function test_receivable_payable_aging_dashboard_page_loads(): void
    {
        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.index'));

        $response->assertOk();
        $response->assertSee('data-testid="receivable-payable-aging-dashboard"', false);
        $response->assertSee('لوحة أعمار الذمم');
        $response->assertSee('تاريخ التقرير: 2026-07-06');
        $response->assertSee('data-testid="aging-dashboard-customer-summary"', false);
        $response->assertSee('data-testid="aging-dashboard-supplier-summary"', false);
        $response->assertSee('إجمالي ذمم العملاء المفتوحة');
        $response->assertSee('إجمالي ذمم الموردين المفتوحة');
        $response->assertSee('data-testid="aging-dashboard-customer-report-link"', false);
        $response->assertSee('data-testid="aging-dashboard-supplier-report-link"', false);
    }

    public function test_reports_index_displays_receivable_payable_aging_dashboard_link(): void
    {
        if (! view()->exists('reports.index')) {
            $this->markTestSkipped('reports.index view does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('data-testid="receivable-payable-aging-dashboard-link"', false);
        $response->assertSee('لوحة أعمار الذمم');
        $response->assertSee(route('reports.receivable-payable-aging-dashboard.index'), false);
    }
}
