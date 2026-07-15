<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase66BManagementRegistryAlignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_66b_contract_files_exist(): void
    {
        $this->assertFileExists(base_path('docs/phase-66b-saved-view-management-registry-alignment.json'));
        $this->assertFileExists(base_path('docs/phase-66b-saved-view-management-registry-alignment.md'));
    }

    public function test_report_saved_view_controller_uses_registry_instead_of_static_maps(): void
    {
        $controller = file_get_contents(app_path('Http/Controllers/ReportSavedViewController.php'));

        $this->assertStringContainsString('use App\Support\Reports\ReportSavedViewRegistry;', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::find($reportKey)', $controller);
        $this->assertStringContainsString('ReportSavedViewRegistry::indexRoute($reportKey)', $controller);

        $this->assertStringNotContainsString('private const REPORT_LABELS', $controller);
        $this->assertStringNotContainsString('private const REPORT_ROUTES', $controller);
        $this->assertStringNotContainsString('self::REPORT_LABELS', $controller);
        $this->assertStringNotContainsString('self::REPORT_ROUTES', $controller);
    }

    public function test_management_page_displays_registry_labels_for_every_registered_saved_view_report(): void
    {
        $user = User::factory()->create();

        foreach (ReportSavedViewRegistry::keys() as $key) {
            ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => $key,
                'name' => 'عرض ' . $key,
                'filters' => [],
                'is_default' => false,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk();

        foreach (ReportSavedViewRegistry::reports() as $report) {
            $response->assertSee($report['label']);
        }

        $response->assertSee('data-testid="report-saved-view-open-link"', false);
        $response->assertSee('data-testid="report-saved-view-apply-link"', false);
    }

    public function test_apply_uses_registry_index_route_for_every_registered_report(): void
    {
        $user = User::factory()->create();

        foreach (ReportSavedViewRegistry::reports() as $report) {
            $routeName = $report['index_route'];

            $this->assertTrue(Route::has($routeName), $routeName);

            $savedView = ReportSavedView::query()->create([
                'user_id' => $user->id,
                'report_key' => $report['key'],
                'name' => 'عرض ' . $report['key'],
                'filters' => [],
                'is_default' => false,
            ]);

            $this->actingAs($user)
                ->get(route('reports.saved-views.apply', $savedView->id))
                ->assertRedirect(route($routeName, [
                    'saved_view_id' => $savedView->id,
                ]));
        }
    }

    public function test_unknown_report_keys_remain_safe_without_management_route_url(): void
    {
        $user = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'unknown-report-key',
            'name' => 'عرض غير معروف',
            'filters' => [],
            'is_default' => false,
        ]);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee('unknown-report-key');

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('report_key', 'unknown-report-key')
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('reports.saved-views.apply', $savedView->id))
            ->assertRedirect(route('reports.saved-views.index'));
    }

    public function test_phase_66b_json_contract_documents_registry_alignment(): void
    {
        $contract = json_decode(
            file_get_contents(base_path('docs/phase-66b-saved-view-management-registry-alignment.json')),
            true
        );

        $this->assertSame('Phase 66B', $contract['phase']);
        $this->assertSame('Phase 66A clean', $contract['baseline']['phase']);
        $this->assertSame('dac5b45', $contract['baseline']['commit']);
        $this->assertTrue($contract['registry_alignment']['replace_static_report_labels']);
        $this->assertTrue($contract['registry_alignment']['replace_static_report_routes']);
        $this->assertSame('ReportSavedViewRegistry::find', $contract['registry_alignment']['label_source']);
        $this->assertSame('ReportSavedViewRegistry::indexRoute', $contract['registry_alignment']['route_source']);
        $this->assertContains('registry_alignment_gap', $contract['resolved_findings']);
    }
}
