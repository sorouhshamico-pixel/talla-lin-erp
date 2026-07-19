<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewTag;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReportSavedViewPhase81CSharingFinalizationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_phase_81c_documents_finalization_scope(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-81c-saved-view-sharing-finalization.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-81c-saved-view-sharing-finalization.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertSame(
            'Phase 81C',
            $document['phase']
        );
        $this->assertSame(
            'finalization',
            $document['type']
        );
        $this->assertSame(
            'Phase 81B',
            $document['baseline']['phase']
        );
        $this->assertSame(
            'd44cf27',
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
        $this->assertFalse(
            $document['scope']
                ['csv_format_changes_expected']
        );
        $this->assertTrue(
            $document['scope']
                ['documentation_and_tests_only']
        );
        $this->assertSame(
            'Phase 82A',
            $document['next_recommendation']['phase']
        );
    }

    public function test_final_schema_model_relations_and_routes_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_shares'
            )
        );

        foreach ([
            'report_saved_view_id',
            'owner_user_id',
            'recipient_user_id',
            'permission',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn(
                    'report_saved_view_shares',
                    $column
                )
            );
        }

        $savedView = new ReportSavedView();
        $share = new ReportSavedViewShare();

        foreach ([
            'shares',
            'sharedWithUsers',
        ] as $method) {
            $this->assertTrue(
                method_exists($savedView, $method)
            );
        }

        foreach ([
            'savedView',
            'owner',
            'recipient',
            'canUse',
        ] as $method) {
            $this->assertTrue(
                method_exists($share, $method)
            );
        }

        foreach ([
            'reports.saved-views.shares.index' => 'GET',
            'reports.saved-views.shares.store' => 'POST',
            'reports.saved-view-shares.update' => 'PATCH',
            'reports.saved-view-shares.destroy' => 'DELETE',
            'reports.shared-saved-views.index' => 'GET',
            'reports.shared-saved-views.copy' => 'POST',
            'reports.shared-saved-views.apply' => 'GET',
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

    public function test_final_owner_and_recipient_permission_scope(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $foreign = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Final Permission Scope'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $this->assertSame(
            'view',
            $share->permission
        );
        $this->assertFalse(
            $share->canUse()
        );

        $updated = $service->updatePermission(
            $owner,
            $share,
            'use'
        );

        $this->assertSame(
            'use',
            $updated->permission
        );
        $this->assertTrue(
            $updated->canUse()
        );

        $this->actingAs($foreign)
            ->patch(
                route(
                    'reports.saved-view-shares.update',
                    $share
                ),
                ['permission' => 'view']
            )
            ->assertNotFound();

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertRedirect();
    }

    public function test_final_archive_restore_copy_and_delete_boundaries(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Final Lifecycle',
            ['from_date' => '2026-07-01']
        );

        $tag = ReportSavedViewTag::query()->create([
            'user_id' => $owner->id,
            'name' => 'Final Owner Tag',
            'normalized_name' => 'final owner tag',
            'color' => '#64748B',
        ]);

        $savedView->tags()->attach($tag->id);

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $savedView->forceFill([
            'archived_at' => now(),
        ])->save();

        $this->assertTrue(
            $service->listReceived($recipient)->isEmpty()
        );

        $this->actingAs($recipient)
            ->post(
                route(
                    'reports.shared-saved-views.copy',
                    $share
                )
            )
            ->assertNotFound();

        $savedView->forceFill([
            'archived_at' => null,
        ])->save();

        $copy = $service->copyToRecipient(
            $recipient,
            $share
        );

        $this->assertSame(
            $recipient->id,
            $copy->user_id
        );
        $this->assertFalse(
            $copy->is_default
        );
        $this->assertNull(
            $copy->archived_at
        );
        $this->assertFalse(
            $copy->tags()->exists()
        );
        $this->assertFalse(
            $copy->shares()->exists()
        );

        $savedView->delete();

        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $share->id]
        );
    }

    public function test_final_ui_and_csv_boundaries_remain_locked(): void
    {
        $ownerView = file_get_contents(
            resource_path(
                'views/reports/saved-views/shares.blade.php'
            )
        );
        $recipientView = file_get_contents(
            resource_path(
                'views/reports/shared-saved-views/index.blade.php'
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
            'report-saved-view-share-manager',
            'report-saved-view-share-recipient',
            'report-saved-view-share-permission',
            'report-saved-view-share-revoke-button',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $ownerView
            );
        }

        foreach ([
            'shared-saved-views-page',
            'shared-saved-view-row',
            'shared-saved-view-apply-button',
            'shared-saved-view-copy-button',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $recipientView
            );
        }

        foreach ([
            'report_saved_view_shares',
            'owner_user_id',
            'recipient_user_id',
            'permission',
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
            '## Phase 81B — Saved View Sharing',
            'Phase 81C — Finalize Saved View Sharing',
        ] as $marker) {
            $this->assertStringContainsString(
                $marker,
                $agents
            );
        }
    }

    private function savedView(
        User $user,
        string $name,
        array $filters = []
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => $filters,
            'is_default' => false,
        ]);
    }
}
