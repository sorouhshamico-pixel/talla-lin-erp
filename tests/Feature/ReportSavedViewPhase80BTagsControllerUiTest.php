<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase80BTagsControllerUiTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_by_owned_tag(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $user = User::factory()->create();

        $tag = $tagService->create(
            $user,
            'شهري'
        );
        $tagged = $this->savedView(
            $user,
            'Tagged View'
        );
        $plain = $this->savedView(
            $user,
            'Plain View'
        );

        $tagService->syncSavedViewTags(
            $user,
            $tagged,
            [$tag->id]
        );

        $this->actingAs($user)
            ->get(
                route(
                    'reports.saved-views.index',
                    [
                        'tag_ids' => [$tag->id],
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Tagged View')
            ->assertDontSee('Plain View')
            ->assertSee(
                'data-testid="report-saved-views-tag-filter"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-tag-manager"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-tags-sync-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-bulk-attach-tags-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-bulk-detach-tags-button"',
                false
            );
    }

    public function test_filtered_export_respects_tag(): void
    {
        $tagService = app(
            ReportSavedViewTagService::class
        );
        $user = User::factory()->create();

        $tag = $tagService->create(
            $user,
            'تصدير'
        );
        $included = $this->savedView(
            $user,
            'Included View'
        );
        $excluded = $this->savedView(
            $user,
            'Excluded View'
        );

        $tagService->syncSavedViewTags(
            $user,
            $included,
            [$tag->id]
        );

        $csv = $this->actingAs($user)
            ->get(
                route(
                    'reports.saved-views.export',
                    [
                        'tag_ids' => [$tag->id],
                    ]
                )
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            )
            ->streamedContent();

        $this->assertStringContainsString(
            'Included View',
            $csv
        );
        $this->assertStringNotContainsString(
            'Excluded View',
            $csv
        );
    }

    public function test_csv_runtime_sources_remain_unchanged(): void
    {
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

    public function test_historical_controller_markers_remain(): void
    {
        $controller = file_get_contents(
            app_path(
                'Http/Controllers/'
                . 'ReportSavedViewController.php'
            )
        );

        foreach ([
            '$savedViewService->paginateForManagement(',
            '$savedViewService->exportForManagement(',
            "'saved-views-' . now()->format('Ymd-His') . '.csv'",
            'response()->streamDownload(',
            '$this->csvExportWriter->write($formattedSavedViews)',
            "'Content-Type' => 'text/csv; charset=UTF-8'",
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $controller
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
