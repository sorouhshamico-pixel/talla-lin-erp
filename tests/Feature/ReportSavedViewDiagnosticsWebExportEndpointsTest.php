<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewDiagnosticsWebExportEndpointsTest extends TestCase
{
    public function test_report_saved_view_diagnostics_export_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('reports.saved-view-diagnostics.markdown'));
        $this->assertTrue(Route::has('reports.saved-view-diagnostics.json'));

        $markdownRoute = Route::getRoutes()->getByName('reports.saved-view-diagnostics.markdown');
        $jsonRoute = Route::getRoutes()->getByName('reports.saved-view-diagnostics.json');

        $this->assertSame('reports/saved-view-diagnostics/markdown', $markdownRoute->uri());
        $this->assertSame('reports/saved-view-diagnostics/json', $jsonRoute->uri());

        $this->assertContains('auth', $markdownRoute->middleware());
        $this->assertContains('auth', $jsonRoute->middleware());
    }

    public function test_diagnostics_export_endpoints_require_authentication(): void
    {
        $this->get(route('reports.saved-view-diagnostics.markdown'))
            ->assertRedirect();

        $this->get(route('reports.saved-view-diagnostics.json'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_view_markdown_diagnostics_export(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.markdown'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8')
            ->assertSee('# Report Saved View Registry Diagnostic Report', false)
            ->assertSee('- Report count: 3', false)
            ->assertSee('### sales-invoice-aging', false)
            ->assertSee('### customer-sales-invoice-aging', false)
            ->assertSee('### customer-sales-invoice-aging', false)
            ->assertSee('- Hidden fields: customer_id, payment_status, aging_bucket', false);
    }

    public function test_authenticated_user_can_view_json_diagnostics_export(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.json'))
            ->assertOk()
            ->assertJsonPath('title', 'Report Saved View Registry Diagnostic Report')
            ->assertJsonPath('summary.report_count', 3)
            ->assertJsonPath('summary.invalid_count', 0)
            ->assertJsonPath('summary.valid', true)

            ;
    }

    public function test_diagnostics_web_view_contains_export_links(): void
    {
        $user = User::factory()->make([
            'id' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-view-diagnostics.index'))
            ->assertOk()
            ->assertSee('Export')
            ->assertSee('View Markdown')
            ->assertSee('View JSON')
            ->assertSee(route('reports.saved-view-diagnostics.markdown'), false)
            ->assertSee(route('reports.saved-view-diagnostics.json'), false);
    }

    public function test_phase_59b_web_export_endpoints_are_documented(): void
    {
        $doc = base_path('docs/phase-59-report-saved-view-diagnostics-web-export-endpoints.md');
        $view = base_path('resources/views/reports/saved-view-diagnostics.blade.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($view);

        $contents = file_get_contents($doc);
        $viewContents = file_get_contents($view);

        $this->assertStringContainsString('Phase 59B', $contents);
        $this->assertStringContainsString('Report Saved View Diagnostics Web Export Endpoints', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.markdown', $contents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.json', $contents);
        $this->assertStringContainsString('ReportSavedViewDiagnosticsWebExportEndpointsTest', $contents);

        $this->assertStringContainsString('report-saved-view-diagnostics-export-actions', $viewContents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.markdown', $viewContents);
        $this->assertStringContainsString('reports.saved-view-diagnostics.json', $viewContents);
    }
}
