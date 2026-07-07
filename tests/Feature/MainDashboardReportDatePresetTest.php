<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MainDashboardReportDatePresetTest extends TestCase
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

    public function test_main_dashboard_displays_report_date_preset_links(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('data-testid="main-dashboard-financial-date-presets"', false);
        $response->assertSee('data-testid="main-dashboard-financial-date-preset-today"', false);
        $response->assertSee('data-testid="main-dashboard-financial-date-preset-month-end"', false);
        $response->assertSee('data-testid="main-dashboard-financial-date-preset-previous-month-end"', false);
        $response->assertSee('data-testid="main-dashboard-financial-date-preset-quarter-end"', false);

        $response->assertSee(route('dashboard', ['as_of_date' => '2026-07-06']), false);
        $response->assertSee(route('dashboard', ['as_of_date' => '2026-07-31']), false);
        $response->assertSee(route('dashboard', ['as_of_date' => '2026-06-30']), false);
        $response->assertSee(route('dashboard', ['as_of_date' => '2026-09-30']), false);
    }

    public function test_report_date_preset_links_preserve_selected_branch(): void
    {
        if (! Route::has('dashboard')) {
            $this->markTestSkipped('dashboard route does not exist.');
        }

        $branchId = (int) DB::table('branches')->orderBy('id')->value('id');

        $user = User::query()->firstOrFail();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'branch_id' => $branchId,
            'as_of_date' => '2026-07-20',
        ]));

        $response->assertOk();
        $response->assertSee(e(route('dashboard', ['branch_id' => $branchId, 'as_of_date' => '2026-07-06'])), false);
        $response->assertSee(e(route('dashboard', ['branch_id' => $branchId, 'as_of_date' => '2026-07-31'])), false);
        $response->assertSee(e(route('dashboard', ['branch_id' => $branchId, 'as_of_date' => '2026-06-30'])), false);
        $response->assertSee(e(route('dashboard', ['branch_id' => $branchId, 'as_of_date' => '2026-09-30'])), false);
    }
}
