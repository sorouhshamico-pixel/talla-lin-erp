<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReceivablePayableAgingDashboardFilterContextTest extends TestCase
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

    public function test_aging_dashboard_print_displays_filter_context(): void
    {
        $user = User::query()->firstOrFail();
        $branch = DB::table('branches')->orderBy('id')->first();

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.print', [
            'branch_id' => $branch->id,
            'as_of_date' => '2026-07-31',
        ]));

        $response->assertOk();
        $response->assertSee('data-testid="aging-dashboard-print-filter-context"', false);
        $response->assertSee('الفرع');
        $response->assertSee($branch->name);
        $response->assertSee('تاريخ التقرير المحدد');
        $response->assertSee('2026-07-31');
    }

    public function test_aging_dashboard_export_displays_filter_context(): void
    {
        $user = User::query()->firstOrFail();
        $branch = DB::table('branches')->orderBy('id')->first();

        $response = $this->actingAs($user)->get(route('reports.receivable-payable-aging-dashboard.export', [
            'branch_id' => $branch->id,
            'as_of_date' => '2026-07-31',
        ]));

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('الفرع', $content);
        $this->assertStringContainsString($branch->name, $content);
        $this->assertStringContainsString('تاريخ التقرير المحدد', $content);
        $this->assertStringContainsString('2026-07-31', $content);
    }
}
