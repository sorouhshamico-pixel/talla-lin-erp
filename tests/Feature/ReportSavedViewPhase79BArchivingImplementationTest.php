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

class ReportSavedViewPhase79BArchivingImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_79b_documents_migration_and_model_contract(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-79b-saved-view-archiving-'
                . 'implementation.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-79b-saved-view-archiving-'
                . 'implementation.md'
            )
        );

        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-79b-saved-view-archiving-'
                    . 'implementation.json'
                )
            ),
            true
        );

        $this->assertSame('Phase 79B', $document['phase']);
        $this->assertSame('implementation', $document['type']);
        $this->assertSame('Phase 79A', $document['baseline']['phase']);
        $this->assertSame('399bd33', $document['baseline']['commit']);
        $this->assertTrue(
            $document['implementation']
                ['archived_at_migration_added']
        );
        $this->assertFalse(
            $document['csv_contract']['schema_change']
        );
        $this->assertSame(
            'Phase 79C',
            $document['next_recommendation']['phase']
        );

        $migrationPath = base_path(
            'database/migrations/'
            . '2026_07_16_160000_add_archived_at_'
            . 'to_report_saved_views_table.php'
        );
        $this->assertFileExists($migrationPath);
        $migration = file_get_contents($migrationPath);

        $this->assertStringContainsString(
            "\$table->timestamp('archived_at')->nullable()",
            $migration
        );
        $this->assertStringContainsString(
            "['user_id', 'archived_at']",
            $migration
        );
        $this->assertStringContainsString(
            'report_saved_views_user_archived_at_index',
            $migration
        );
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

        $active = $this->createSavedView(
            User::factory()->create(),
            'profit-loss',
            'Existing Active',
            false
        );

        $this->assertNull($active->archived_at);
        $this->assertTrue($active->isActive());
        $this->assertFalse($active->isArchived());

        $active->forceFill(['archived_at' => now()])->save();

        $this->assertTrue($active->fresh()->isArchived());
        $this->assertFalse($active->fresh()->isActive());
    }

    public function test_lifecycle_routes_are_authenticated_patch_routes(): void
    {
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

        $this->patch(
            route(
                'reports.saved-views.bulk-archive'
            ),
            ['saved_view_ids' => [1]]
        )->assertRedirect(route('login'));
    }

    public function test_management_status_validation_and_modes(): void
    {
        $user = User::factory()->create();
        $active = $this->createSavedView(
            $user,
            'profit-loss',
            'Active View',
            false
        );
        $archived = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Archived View',
            false,
            now()
        );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'status' => 'invalid',
            ]))
            ->assertSessionHasErrors('status');

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($archived->name)
            ->assertSee(
                'data-testid="report-saved-views-status-select"',
                false
            );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'status' => 'archived',
            ]))
            ->assertOk()
            ->assertSee($archived->name)
            ->assertDontSee($active->name)
            ->assertSee(
                'data-testid="report-saved-views-active-status"',
                false
            );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'status' => 'all',
            ]))
            ->assertOk()
            ->assertSee($active->name)
            ->assertSee($archived->name);
    }

    public function test_management_service_status_and_report_queries(): void
    {
        $user = User::factory()->create();
        $service = app(ReportSavedViewService::class);

        $active = $service->save(
            $user,
            'profit-loss',
            'Service Active',
            [],
            true
        );
        $archived = $service->save(
            $user,
            'profit-loss',
            'Service Archived',
            [],
            false
        );
        $service->archive($user, $archived->id);

        $this->assertSame(
            [$active->id],
            $service
                ->listForReport($user, 'profit-loss')
                ->pluck('id')
                ->all()
        );
        $this->assertSame(
            $active->id,
            $service->getDefault(
                $user,
                'profit-loss'
            )?->id
        );

        $activePage = $service->paginateForManagement(
            $user,
            null,
            null,
            [],
            [],
            15,
            'active'
        );
        $archivedPage = $service->paginateForManagement(
            $user,
            null,
            null,
            [],
            [],
            15,
            'archived'
        );
        $allPage = $service->paginateForManagement(
            $user,
            null,
            null,
            [],
            [],
            15,
            'all'
        );

        $this->assertSame(
            [$active->id],
            $activePage->pluck('id')->all()
        );
        $this->assertSame(
            [$archived->id],
            $archivedPage->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$active->id, $archived->id],
            $allPage->pluck('id')->all()
        );

        $service->archive($user, $active->id);

        $this->assertNull(
            $service->getDefault(
                $user,
                'profit-loss'
            )
        );
        $this->assertTrue(
            $service
                ->listForReport($user, 'profit-loss')
                ->isEmpty()
        );
    }

    public function test_filtered_export_respects_status_and_selected_export_can_include_archived(): void
    {
        $user = User::factory()->create();
        $active = $this->createSavedView(
            $user,
            'profit-loss',
            'CSV Active',
            false
        );
        $archived = $this->createSavedView(
            $user,
            'profit-loss',
            'CSV Archived',
            false,
            now()
        );

        $archivedCsv = $this->actingAs($user)
            ->get(route('reports.saved-views.export', [
                'status' => 'archived',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(
            $archived->name,
            $archivedCsv
        );
        $this->assertStringNotContainsString(
            $active->name,
            $archivedCsv
        );

        $allCsv = $this->actingAs($user)
            ->get(route('reports.saved-views.export', [
                'status' => 'all',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString(
            $active->name,
            $allCsv
        );
        $this->assertStringContainsString(
            $archived->name,
            $allCsv
        );

        $selectedCsv = $this->actingAs($user)
            ->post(
                route(
                    'reports.saved-views.export-selected'
                ),
                ['saved_view_ids' => [$archived->id]]
            )
            ->assertOk()
            ->streamedContent();

        $rows = $this->parseCsv($selectedCsv);

        $this->assertCount(2, $rows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame($archived->name, $rows[1][1]);
        $this->assertNotContains(
            'archived_at',
            $rows[0]
        );
    }

    public function test_single_archive_restore_and_archived_action_guards(): void
    {
        $user = User::factory()->create();
        $savedView = $this->createSavedView(
            $user,
            'profit-loss',
            'Single Lifecycle',
            true
        );

        $this->actingAs($user)
            ->patch(
                route(
                    'reports.saved-views.archive',
                    $savedView
                ),
                ['return_status' => 'all']
            )
            ->assertRedirect(
                route(
                    'reports.saved-views.index',
                    ['status' => 'all']
                )
            )
            ->assertSessionHas(
                'status',
                'تمت أرشفة العرض المحفوظ.'
            );

        $savedView->refresh();

        $this->assertTrue($savedView->isArchived());
        $this->assertFalse($savedView->is_default);

        $this->actingAs($user)
            ->get(
                route(
                    'reports.saved-views.apply',
                    $savedView
                )
            )
            ->assertNotFound();

        $this->actingAs($user)
            ->get(
                route(
                    'reports.saved-views.edit',
                    $savedView
                )
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
                ['name' => 'Rejected Update']
            )
            ->assertNotFound();

        $this->actingAs($user)
            ->patch(
                route(
                    'reports.saved-views.restore',
                    $savedView
                ),
                ['return_status' => 'archived']
            )
            ->assertRedirect(
                route(
                    'reports.saved-views.index',
                    ['status' => 'archived']
                )
            )
            ->assertSessionHas(
                'status',
                'تمت استعادة العرض المحفوظ.'
            );

        $savedView->refresh();

        $this->assertTrue($savedView->isActive());
        $this->assertFalse($savedView->is_default);
    }

    public function test_bulk_archive_restore_normalizes_ids_and_enforces_user_scope(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $service = app(ReportSavedViewService::class);

        $first = $this->createSavedView(
            $user,
            'profit-loss',
            'Bulk First',
            true
        );
        $second = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Bulk Second',
            false
        );
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Bulk Foreign',
            true
        );

        $this->assertSame(
            2,
            $service->bulkArchive(
                $user,
                [
                    $first->id,
                    $second->id,
                    $first->id,
                    $foreign->id,
                    0,
                    -1,
                    999999,
                ]
            )
        );

        $this->assertTrue($first->fresh()->isArchived());
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->isArchived());
        $this->assertTrue($foreign->fresh()->isActive());
        $this->assertTrue($foreign->fresh()->is_default);

        $this->assertSame(
            2,
            $service->bulkRestore(
                $user,
                [
                    $second->id,
                    $first->id,
                    $second->id,
                    $foreign->id,
                ]
            )
        );

        $this->assertTrue($first->fresh()->isActive());
        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->isActive());

        $this->actingAs($user)
            ->patch(
                route(
                    'reports.saved-views.bulk-archive'
                ),
                [
                    'saved_view_ids' => [
                        $first->id,
                        $first->id,
                    ],
                ]
            )
            ->assertSessionHasErrors('saved_view_ids.1');
    }

    public function test_save_reactivates_matching_archived_row_and_imported_rows_are_active(): void
    {
        $sourceUser = User::factory()->create();
        $targetUser = User::factory()->create();
        $service = app(ReportSavedViewService::class);

        $savedView = $service->save(
            $sourceUser,
            'sales-invoice-aging',
            'Reactivate Existing',
            [
                'payment_status' => 'partial',
            ],
            false
        );
        $service->archive($sourceUser, $savedView->id);

        $reactivated = $service->save(
            $sourceUser,
            'sales-invoice-aging',
            'Reactivate Existing',
            [
                'payment_status' => 'paid',
            ],
            true
        );

        $this->assertSame($savedView->id, $reactivated->id);
        $this->assertTrue($reactivated->fresh()->isActive());
        $this->assertTrue($reactivated->fresh()->is_default);
        $this->assertSame(
            ['payment_status' => 'paid'],
            $reactivated->fresh()->filters
        );

        $service->archive($sourceUser, $reactivated->id);

        $csv = $this->actingAs($sourceUser)
            ->post(
                route(
                    'reports.saved-views.export-selected'
                ),
                ['saved_view_ids' => [$reactivated->id]]
            )
            ->assertOk()
            ->streamedContent();

        $this->actingAs($targetUser)
            ->post(
                route(
                    'reports.saved-views.import-apply'
                ),
                ['csv_payload' => base64_encode($csv)]
            )
            ->assertRedirect(
                route('reports.saved-views.index')
            );

        $imported = ReportSavedView::query()
            ->where('user_id', $targetUser->id)
            ->where('name', 'Reactivate Existing')
            ->firstOrFail();

        $this->assertTrue($imported->isActive());
    }

    public function test_management_view_shows_status_badges_and_lifecycle_actions(): void
    {
        $user = User::factory()->create();

        $this->createSavedView(
            $user,
            'profit-loss',
            'View Active',
            false
        );
        $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'View Archived',
            false,
            now()
        );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index', [
                'status' => 'all',
            ]))
            ->assertOk()
            ->assertSee(
                'data-testid="report-saved-views-status-select"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-active-badge"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-archived-badge"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-archive-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-restore-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-bulk-archive-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-bulk-restore-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-bulk-delete-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-export-selected-button"',
                false
            );

        $view = file_get_contents(
            resource_path(
                'views/reports/saved-views/index.blade.php'
            )
        );

        foreach ([
            "route('reports.saved-views.bulk-archive')",
            "route('reports.saved-views.bulk-restore')",
            "route('reports.saved-views.archive'",
            "route('reports.saved-views.restore'",
            'bulkArchiveButton.disabled = selectedCount === 0',
            'bulkRestoreButton.disabled = selectedCount === 0',
            'name="return_status"',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $view
            );
        }
    }

    public function test_permanent_delete_still_deletes_active_and_archived_rows(): void
    {
        $user = User::factory()->create();
        $active = $this->createSavedView(
            $user,
            'profit-loss',
            'Delete Active',
            false
        );
        $archived = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Delete Archived',
            false,
            now()
        );

        $this->actingAs($user)
            ->post(
                route(
                    'reports.saved-views.bulk-destroy'
                ),
                [
                    '_method' => 'DELETE',
                    'saved_view_ids' => [
                        $active->id,
                        $archived->id,
                    ],
                    'return_status' => 'all',
                ]
            )
            ->assertRedirect(
                route(
                    'reports.saved-views.index',
                    ['status' => 'all']
                )
            )
            ->assertSessionHas(
                'status',
                'تم حذف 2 من العروض المحددة.'
            );

        $this->assertDatabaseMissing(
            'report_saved_views',
            ['id' => $active->id]
        );
        $this->assertDatabaseMissing(
            'report_saved_views',
            ['id' => $archived->id]
        );
    }

    public function test_phase_79b_source_boundaries_are_exact(): void
    {
        $model = file_get_contents(
            app_path('Models/ReportSavedView.php')
        );
        $service = file_get_contents(
            app_path('Services/ReportSavedViewService.php')
        );
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/ReportSavedViewController.php'
            )
        );
        $routes = file_get_contents(base_path('routes/web.php'));
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
            'private function applyManagementStatus(',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $service
            );
        }

        foreach ([
            "'status' =>",
            'public function archive(',
            'public function restore(',
            'public function bulkArchive(',
            'public function bulkRestore(',
            'private function authorizeActiveSavedView(',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $controller
            );
        }

        foreach ([
            'reports.saved-views.archive',
            'reports.saved-views.restore',
            'reports.saved-views.bulk-archive',
            'reports.saved-views.bulk-restore',
        ] as $marker) {
            $this->assertStringContainsString($marker, $routes);
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
