<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewTag;
use App\Models\User;
use App\Services\ReportSavedViewService;
use App\Services\ReportSavedViewTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportSavedViewPhase80CTagsFinalizationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_phase_80c_documents_finalization_scope(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-80c-saved-view-tags-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-80c-saved-view-tags-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame(
            'Phase 80C',
            $document['phase']
        );
        $this->assertSame(
            'finalization',
            $document['type']
        );
        $this->assertSame(
            'Phase 80B',
            $document['baseline']['phase']
        );
        $this->assertSame(
            '4e7cbf2',
            $document['baseline']['commit']
        );
        $this->assertFalse(
            $document['scope']
                ['runtime_changes_expected']
        );
        $this->assertFalse(
            $document['scope']
                ['database_changes_expected']
        );
        $this->assertFalse(
            $document['scope']
                ['migration_changes_expected']
        );
        $this->assertTrue(
            $document['scope']
                ['documentation_and_tests_only']
        );
        $this->assertSame(
            'Phase 81A',
            $document['next_recommendation']['phase']
        );
    }

    public function test_final_schema_models_and_routes_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_tags'
            )
        );
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_tag'
            )
        );

        foreach ([
            'user_id',
            'name',
            'normalized_name',
            'color',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn(
                    'report_saved_view_tags',
                    $column
                )
            );
        }

        $savedView = new ReportSavedView();
        $tag = new ReportSavedViewTag();

        $this->assertTrue(
            method_exists($savedView, 'tags')
        );
        $this->assertTrue(
            method_exists($tag, 'savedViews')
        );

        foreach ([
            'reports.saved-view-tags.store' => 'POST',
            'reports.saved-view-tags.update' => 'PATCH',
            'reports.saved-view-tags.destroy' => 'DELETE',
            'reports.saved-views.tags.sync' => 'PUT',
            'reports.saved-views.bulk-attach-tags' => 'POST',
            'reports.saved-views.bulk-detach-tags' => 'DELETE',
        ] as $routeName => $method) {
            $route = Route::getRoutes()
                ->getByName($routeName);

            $this->assertNotNull(
                $route,
                $routeName
            );
            $this->assertContains(
                $method,
                $route->methods()
            );
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_final_ownership_and_assignment_scope(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownedTag = $tagService->create(
            $user,
            'نهائي',
            '#123ABC'
        );
        $foreignTag = $tagService->create(
            $otherUser,
            'أجنبي'
        );

        $ownedView = $this->savedView(
            $user,
            'Owned'
        );
        $foreignView = $this->savedView(
            $otherUser,
            'Foreign'
        );

        $tagService->syncSavedViewTags(
            $user,
            $ownedView,
            [
                $ownedTag->id,
                $foreignTag->id,
            ]
        );

        $this->assertSame(
            [$ownedTag->id],
            $ownedView->tags()
                ->pluck('id')
                ->all()
        );
        $this->assertFalse(
            $foreignView->tags()->exists()
        );

        $this->assertSame(
            1,
            $tagService->bulkAttach(
                $user,
                [
                    $ownedView->id,
                    $foreignView->id,
                ],
                [
                    $ownedTag->id,
                    $foreignTag->id,
                ]
            )
        );

        $this->assertSame(
            1,
            $tagService->bulkDetach(
                $user,
                [
                    $ownedView->id,
                    $foreignView->id,
                ],
                [$ownedTag->id]
            )
        );

        $this->assertFalse(
            $ownedView->tags()->exists()
        );
        $this->assertFalse(
            $foreignView->tags()->exists()
        );
    }

    public function test_final_filter_and_lifecycle_contract(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $savedViewService = app(
            ReportSavedViewService::class
        );
        $user = User::factory()->create();

        $firstTag = $tagService->create(
            $user,
            'الأول'
        );
        $secondTag = $tagService->create(
            $user,
            'الثاني'
        );

        $active = $this->savedView(
            $user,
            'Active'
        );
        $archived = $this->savedView(
            $user,
            'Archived'
        );
        $plain = $this->savedView(
            $user,
            'Plain'
        );

        $tagService->syncSavedViewTags(
            $user,
            $active,
            [$firstTag->id]
        );
        $tagService->syncSavedViewTags(
            $user,
            $archived,
            [$secondTag->id]
        );

        $savedViewService->archive(
            $user,
            $archived->id
        );

        $all = $savedViewService
            ->paginateForManagementByTags(
                $user,
                null,
                null,
                [],
                [],
                15,
                'all',
                [
                    $firstTag->id,
                    $secondTag->id,
                ]
            );

        $this->assertEqualsCanonicalizing(
            [
                $active->id,
                $archived->id,
            ],
            $all->getCollection()
                ->pluck('id')
                ->all()
        );
        $this->assertFalse(
            $all->getCollection()
                ->pluck('id')
                ->contains($plain->id)
        );

        $this->actingAs($user)
            ->post(
                route(
                    'reports.saved-views.duplicate',
                    $active
                )
            )
            ->assertRedirect();

        $duplicate = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Active - نسخة')
            ->firstOrFail();

        $this->assertTrue(
            $duplicate->tags()
                ->whereKey($firstTag->id)
                ->exists()
        );

        $this->assertTrue(
            $archived->fresh()->tags()
                ->whereKey($secondTag->id)
                ->exists()
        );

        $savedViewService->restore(
            $user,
            $archived->id
        );

        $this->assertTrue(
            $archived->fresh()->tags()
                ->whereKey($secondTag->id)
                ->exists()
        );

        $savedViewService->delete(
            $user,
            $archived->id
        );

        $this->assertDatabaseMissing(
            'report_saved_view_tag',
            [
                'report_saved_view_id' =>
                    $archived->id,
            ]
        );
    }

    public function test_final_ui_and_csv_boundaries_remain_locked(): void
    {
        $view = file_get_contents(
            resource_path(
                'views/reports/saved-views/index.blade.php'
            )
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
            'report-saved-views-tag-filter',
            'report-saved-view-tag-badge',
            'report-saved-view-tag-manager',
            'report-saved-view-tags-sync-button',
            'report-saved-views-bulk-attach-tags-button',
            'report-saved-views-bulk-detach-tags-button',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $view
            );
        }

        foreach ([
            "'tag_ids' =>",
            'paginateForManagementByTags(',
            'exportForManagementByTags(',
            "'tags' => \$savedView->tags",
            '$savedViewService->paginateForManagement(',
            '$savedViewService->exportForManagement(',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $controller
            );
        }

        foreach ([
            'tag_ids',
            'report_saved_view_tags',
            'report_saved_view_tag',
        ] as $marker) {
            $this->assertStringNotContainsString(
                $marker,
                $writer
            );
            $this->assertStringNotContainsString(
                $marker,
                $parser
            );
        }
    }

    public function test_main_only_and_historical_contracts_remain(): void
    {
        $agents = file_get_contents(
            base_path('AGENTS.md')
        );

        foreach ([
            '## Main-only workflow',
            'Do not create or push a phase branch.',
            'Do not create a Codex worktree.',
            '### 9. Commit directly on main',
            '### 10. Push only main',
            'Phase 80A — Prepare Saved View Tags Contract',
            'Phase 80B — Implement Saved View Tags',
            'Phase 80C — Finalize Saved View Tags',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $agents
            );
        }
    }

    private function savedView(
        User $user,
        string $name
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);
    }
}
