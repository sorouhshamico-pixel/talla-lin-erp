<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewService;
use App\Support\Reports\ReportSavedViewCsvExportWriter;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase78CSelectedCsvExportFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_78c_documents_main_only_workflow(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-78c-selected-saved-view-csv-export-'
            . 'finalization.json'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-78c-selected-saved-view-csv-export-'
                . 'finalization.md'
            )
        );

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame('Phase 78C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame('Phase 78B', $document['baseline']['phase']);
        $this->assertSame('f886978', $document['baseline']['commit']);
        $this->assertSame(
            'main_only',
            $document['workflow_transition']['mode']
        );
        $this->assertFalse(
            $document['workflow_transition']
                ['phase_branches_allowed']
        );
        $this->assertFalse(
            $document['workflow_transition']
                ['codex_worktrees_allowed']
        );
        $this->assertSame(
            'origin/main only',
            $document['workflow_transition']['push_target']
        );
        $this->assertFalse(
            $document['scope']['runtime_changes_expected']
        );
        $this->assertSame(
            'Phase 79A',
            $document['next_recommendation']['phase']
        );
    }

    public function test_agents_file_enforces_main_only_execution(): void
    {
        $agents = file_get_contents(base_path('AGENTS.md'));

        foreach ([
            '## Main-only workflow',
            'All future phases are implemented directly',
            'Do not create:',
            'Codex worktrees',
            '`agents/*` branches',
            '`phase/*` branches',
            'Only push completed, fully validated commits',
            '### 9. Commit directly on main',
            '### 10. Push only main',
        ] as $marker) {
            $this->assertStringContainsString($marker, $agents);
        }

        foreach ([
            'Do not implement a phase directly on `main`.',
            'Push the phase branch first.',
            '## Branch workflow for every phase',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $agents);
        }
    }

    public function test_selected_export_route_and_writer_boundary_are_final(): void
    {
        $route = Route::getRoutes()->getByName(
            'reports.saved-views.export-selected'
        );

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('GET', $route->methods());
        $this->assertContains('auth', $route->gatherMiddleware());

        $reflection = new ReflectionClass(
            ReportSavedViewCsvExportWriter::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());

        foreach ([
            "fopen('php://output', 'w')",
            'ReportSavedViewImportExportVersionRegistry::exportHeader()',
            'ReportSavedViewImportExportVersionRegistry::currentVersion()',
            '$filtersPayload = json_encode(',
            'fputcsv($handle',
            'fclose($handle)',
        ] as $marker) {
            $this->assertStringContainsString($marker, $source);
        }

        foreach ([
            'ReportSavedViewService',
            'Request $request',
            'response(',
            'auth(',
            'route(',
        ] as $marker) {
            $this->assertStringNotContainsString($marker, $source);
        }
    }

    public function test_final_service_scope_order_and_empty_result_are_locked(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $normal = $this->createSavedView(
            $user,
            'profit-loss',
            'Alpha',
            false
        );
        $default = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Zulu',
            true
        );
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign',
            true
        );

        $service = app(ReportSavedViewService::class);

        $result = $service->exportSelectedForManagement(
            $user,
            [
                $normal->id,
                $foreign->id,
                999999,
                $default->id,
                $normal->id,
                0,
                -1,
            ]
        );

        $this->assertSame([
            $default->id,
            $normal->id,
        ], $result->pluck('id')->all());
        $this->assertFalse($result->contains('id', $foreign->id));

        $this->assertTrue(
            $service->exportSelectedForManagement(
                $user,
                [$foreign->id, 999999]
            )->isEmpty()
        );
    }

    public function test_final_selected_export_bytes_and_header_only_behavior_are_locked(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Final Selected Export',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_61_90',
            ],
            'is_default' => true,
        ]);
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign Final',
            true
        );

        $csv = $this->actingAs($user)
            ->post(route('reports.saved-views.export-selected'), [
                'saved_view_ids' => [
                    $selected->id,
                    $foreign->id,
                ],
            ])
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            )
            ->streamedContent();

        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertCount(2, $rows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame('Final Selected Export', $rows[1][1]);
        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_61_90',
        ], json_decode($rows[1][7], true));
        $this->assertStringNotContainsString('Foreign Final', $csv);

        $emptyCsv = $this->actingAs($user)
            ->post(route('reports.saved-views.export-selected'), [
                'saved_view_ids' => [$foreign->id, 999999],
            ])
            ->assertOk()
            ->streamedContent();

        $emptyRows = $this->parseCsv($emptyCsv);

        $this->assertCount(1, $emptyRows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $emptyRows[0]
        );
    }

    public function test_final_selected_export_round_trip_and_existing_actions_are_preserved(): void
    {
        $sourceUser = User::factory()->create();
        $targetUser = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $sourceUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Final Selected Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_31_60',
            ],
            'is_default' => true,
        ]);
        $kept = $this->createSavedView(
            $sourceUser,
            'profit-loss',
            'Keep Existing',
            false
        );

        $csv = $this->actingAs($sourceUser)
            ->post(route('reports.saved-views.export-selected'), [
                'saved_view_ids' => [$selected->id],
            ])
            ->assertOk()
            ->streamedContent();

        $this->actingAs($targetUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'));

        $this->assertDatabaseHas('report_saved_views', [
            'user_id' => $targetUser->id,
            'name' => 'Final Selected Round Trip',
        ]);

        $filteredCsv = $this->actingAs($sourceUser)
            ->get(route('reports.saved-views.export', [
                'report_key' => 'profit-loss',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(
            $kept->name,
            $filteredCsv
        );
        $this->assertStringNotContainsString(
            $selected->name,
            $filteredCsv
        );
    }

    public function test_final_management_view_keeps_selected_export_and_delete_controls(): void
    {
        $user = User::factory()->create();

        $this->createSavedView(
            $user,
            'profit-loss',
            'Final View Control',
            false
        );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee(
                'data-testid="report-saved-views-export-selected-button"',
                false
            )
            ->assertSee('تصدير المحدد CSV')
            ->assertSee(
                'data-testid="report-saved-views-bulk-delete-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-select-all-checkbox"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-bulk-select-checkbox"',
                false
            );

        $view = file_get_contents(
            resource_path(
                'views/reports/saved-views/index.blade.php'
            )
        );

        foreach ([
            "route('reports.saved-views.export-selected')",
            "route('reports.saved-views.bulk-destroy')",
            'name="_method"',
            'value="DELETE"',
            'bulkExportButton.disabled = selectedCount === 0',
            'submitter === bulkDeleteButton',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    private function createSavedView(
        User $user,
        string $reportKey,
        string $name,
        bool $isDefault
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => $reportKey,
            'name' => $name,
            'filters' => [],
            'is_default' => $isDefault,
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, substr($csv, 3));
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
