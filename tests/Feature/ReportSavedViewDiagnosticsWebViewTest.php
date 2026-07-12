<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewDiagnosticsWebViewTest extends TestCase
{
    public function test_report_saved_view_diagnostics_route_is_registered(): void
    {
        $this->assertTrue(Route::has('reports.saved-view-diagnostics.index'));

        $route = Route::getRoutes()->getByName('reports.saved-view-diagnostics.index');

        $this->assertSame('reports/saved-view-diagnostics', $route->uri());
        $this->assertContains('auth', $route->middleware());
    }

    public function test_report_saved_view_diagnostics_page_requires_authentication(): void
    {
        $this->get(route('reports.saved-view-diagnostics.index'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_view_report_saved_view_diagnostics_page(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.index'))
            ->assertOk()
            ->assertSee('Report Saved View Diagnostics')
            ->assertSee('تشخيص تقارير Saved Views المسجلة في النظام.')
            ->assertSee('Report Count')
            ->assertSee('Invalid Count')
            ->assertSee('Registry Health')
            ->assertSee('Healthy')
            ->assertSee('sales-invoice-aging')
            ->assertSee('تقرير أعمار ذمم فواتير المبيعات')
            ->assertSee('resources/views/reports/sales-invoice-aging.blade.php')
            ->assertSee('resources/views/reports/partials/sales-invoice-aging-saved-view-controls-config.blade.php')
            ->assertSee('customer_id, payment_status, aging_bucket');
    }

    public function test_report_saved_view_diagnostics_view_contains_expected_test_ids(): void
    {
        $view = base_path('resources/views/reports/saved-view-diagnostics.blade.php');

        $this->assertFileExists($view);

        $contents = file_get_contents($view);

        $this->assertStringContainsString('report-saved-view-diagnostics-page', $contents);
        $this->assertStringContainsString('report-saved-view-diagnostics-summary', $contents);
        $this->assertStringContainsString('report-saved-view-diagnostics-valid-keys', $contents);
        $this->assertStringContainsString('report-saved-view-diagnostics-table', $contents);
        $this->assertStringContainsString('report-saved-view-diagnostics-markdown', $contents);
    }

    public function test_phase_59a_diagnostics_web_view_is_documented(): void
    {
        $doc = base_path('docs/phase-59-report-saved-view-diagnostics-web-view.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 59A', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostics Web View', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.index', $contents);
        $this->assertStringContainsString('resources/views/reports/saved-view-diagnostics.blade.php', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsWebViewTest', $contents);
    }
}
