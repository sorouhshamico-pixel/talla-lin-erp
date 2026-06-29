<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\InitialSetupSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(InitialSetupSeeder::class);
    }

    public function test_reports_center_page_is_available(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('reports.center'));

        $response->assertOk();

        $response->assertSee('data-testid="reports-center"', false);
        $response->assertSee('مركز التقارير');
        $response->assertSee('data-testid="reports-center-financial-dashboard-card"', false);
        $response->assertSee('data-testid="reports-center-profit-loss-card"', false);
        $response->assertSee('data-testid="reports-center-profit-loss-export-card"', false);
    }

    public function test_reports_center_contains_expected_report_links(): void
    {
        $this->actingAsOwner();

        $response = $this->get(route('reports.center'));

        $response->assertOk();

        $response->assertSee('data-testid="reports-center-financial-dashboard-link"', false);
        $response->assertSee(route('reports.financial-dashboard'), false);

        $response->assertSee('data-testid="reports-center-profit-loss-link"', false);
        $response->assertSee(route('reports.profit-loss'), false);

        $response->assertSee('data-testid="reports-center-profit-loss-export-link"', false);
        $response->assertSee(route('reports.profit-loss.export'), false);
    }

    private function actingAsOwner(): void
    {
        $user = User::query()->firstOrFail();

        $this->actingAs($user);
    }
}
