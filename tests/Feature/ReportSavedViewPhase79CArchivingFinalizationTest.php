<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewService;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportSavedViewPhase79CArchivingFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_79c_documents_and_main_only_state(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-79c-saved-view-archiving-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-79c-saved-view-archiving-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame('Phase 79C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame('Phase 79B', $document['baseline']['phase']);
        $this->assertSame('0e51cea', $document['baseline']['commit']);
        $this->assertFalse(
            $document['scope']['runtime_changes_expected']
        );
        $this->assertFalse(
            $document['scope']['database_changes_expected']
        );
        $this->assertTrue(
            $document['preserved_behavior']
                ['historical_source_contracts']
        );
        $this->assertSame(
            'Phase 80A',
            $document['next_recommendation']['phase']
        );

        $agents = file_get_contents(base_path('AGENTS.md'));

        foreach ([
            '## Main-only workflow',
            'Phase 79C — Finalize Saved View Archiving',
            'Phase 80A — Select Next Saved View Management Contract',
            'Do not create or push a phase branch.',
            'Do not create a Codex worktree.',
        ] as $marker) {
            $this->assertStringContainsString($marker, $agents);
        }
    }

    public function test_database_model_and_routes_are_finalized(): void
    {
        $this->assertTrue(
            Schema::hasColumn(
                'report_saved_views',
                'archived_at'
            )
        );

        $model = new ReportSavedView();

        $this->assertContains(
            'archived_at',
            $model->getFillable()
        );
        $this->assertSame(
            'datetime',
            $model->getCasts()['archived_at']
        );
        $this->assertTrue(method_exists($model, 'isArchived'));
        $this->assertTrue(method_exists($model, 'isActive'));

        foreach ([
            'reports.saved-views.archive',
            'reports.saved-views.restore',
            'reports.saved-views.bulk-archive',
            'reports.saved-views.bulk-restore',
        ] as $routeName) {
            $route = Route::getRoutes()->getByName($routeName);

            $this->assertNotNull($route, $routeName);
            $this->assertContains('PATCH', $route->methods());
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_final_archive_restore_scope_and_default_rules(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = app(ReportSavedViewService::class);

        $ownedDefault = $this->createSavedView(
            $user,
            'profit-loss',
            'Owned Default',
            true
        );
        $ownedSecond = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Owned Second',
            false
        );
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign',
            true
        );

        $this->assertTrue(
            $service->archive($user, $ownedDefault->id)
        );
        $this->assertFalse(
            $service->archive($user, $ownedDefault->id)
        );

        $ownedDefault->refresh();

        $this->assertTrue($ownedDefault->isArchived());
        $this->assertFalse($ownedDefault->is_default);

        $this->assertSame(
            1,
            $service->bulkArchive(
                $user,
                [
                    $ownedSecond->id,
                    $ownedSecond->id,
                    $foreign->id,
                    0,
                    -1,
                ]
            )
        );

        $this->assertTrue($ownedSecond->fresh()->isArchived());
        $this->assertTrue($foreign->fresh()->isActive());

        $this->assertSame(
            2,
            $service->bulkRestore(
                $user,
                [
                    $ownedDefault->id,
                    $ownedSecond->id,
                    $foreign->id,
                ]
            )
        );

        $this->assertTrue($ownedDefault->fresh()->isActive());
        $this->assertFalse(
            $ownedDefault->fresh()->is_default
        );
        $this->assertTrue($ownedSecond->fresh()->isActive());
        $this->assertTrue($foreign->fresh()->is_default);
    }

    public function test_archived_report_actions_are_rejected(): void
    {
        $user = User::factory()->create();
        $savedView = $this->createSavedView(
            $user,
            'profit-loss',
            'Archived Actions',
            false,
            now()
        );

        $this->actingAs($user)
            ->get(
                route('reports.saved-views.apply', $savedView)
            )
            ->assertNotFound();

        $this->actingAs($user)
            ->get(
                route('reports.saved-views.edit', $savedView)
            )
            ->assertNotFound();

        $this->actingAs($user)
            ->post(
                route(
                    'reports.saved-views.duplicate',
                    $savedView
                )
            )
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(
                route(
                    'reports.saved-views.make-default',
                    $savedView
                )
            )
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(
                route(
                    'reports.saved-views.update',
                    $savedView
                ),
                ['name' => 'Rejected']
            )
            ->assertNotFound();
    }

    public function test_status_modes_view_and_csv_boundary_are_final(): void
    {
        $user = User::factory()->create();
        $active = $this->createSavedView(
            $user,
            'profit-loss',
            'Final Active',
            false
        );
        $archived = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Final Archived',
            false,
            now()
        );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($archived->name);

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'status' => 'archived',
            ]))
            ->assertOk()
            ->assertSee($archived->name)
            ->assertDontSee($active->name)
            ->assertSee(
                'data-testid="report-saved-view-archived-badge"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-restore-button"',
                false
            );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'status' => 'all',
            ]))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee($archived->name)
            ->assertSee(
                'data-testid="report-saved-views-bulk-archive-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-bulk-restore-button"',
                false
            );

        $csv = $this->actingAs($user)
            ->post(
                route(
                    'reports.saved-views.export-selected'
                ),
                ['saved_view_ids' => [$archived->id]]
            )
            ->assertOk()
            ->streamedContent();

        $rows = $this->parseCsv($csv);

        $this->assertCount(2, $rows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame('Final Archived', $rows[1][1]);
        $this->assertNotContains('archived_at', $rows[0]);
    }

    public function test_final_runtime_source_boundaries_remain_locked(): void
    {
        $model = file_get_contents(
            app_path('Models/ReportSavedView.php')
        );
        $service = file_get_contents(
            app_path('Services/ReportSavedViewService.php')
        );
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/'
                . 'ReportSavedViewController.php'
            )
        );
        $writer = file_get_contents(
            app_path(
                'Support/Reports/'
                . 'ReportSavedViewCsvExportWriter.php'
            )
        );
        $parser = file_get_contents(
            app_path(
                'Support/Reports/'
                . 'ReportSavedViewCsvImportParser.php'
            )
        );

        foreach ([
            "'archived_at'",
            'public function isArchived(): bool',
            'public function isActive(): bool',
        ] as $marker) {
            $this->assertStringContainsString($marker, $model);
        }

        foreach ([
            "->whereNull('archived_at')",
            'public function archive(',
            'public function restore(',
            'public function bulkArchive(',
            'public function bulkRestore(',
        ] as $marker) {
            $this->assertStringContainsString($marker, $service);
        }

        foreach ([
            "'status' =>",
            'public function archive(',
            'public function restore(',
            'public function bulkArchive(',
            'public function bulkRestore(',
            'private function authorizeActiveSavedView(',
            "->route('reports.saved-views.index', "
                . '$this->managementReturnQuery($request))',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $controller
            );
        }

        $this->assertStringNotContainsString(
            'archived_at',
            $writer
        );
        $this->assertStringNotContainsString(
            'archived_at',
            $parser
        );
    }

    private function createSavedView(
        User $user,
        string $reportKey,
        string $name,
        bool $isDefault,
        mixed $archivedAt = null
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => $reportKey,
            'name' => $name,
            'filters' => [],
            'is_default' => $isDefault,
            'archived_at' => $archivedAt,
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
