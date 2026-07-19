<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewTag;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase81BSharingIntegrationBoundariesTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_copy_route_is_authenticated(): void
    {
        $route = Route::getRoutes()->getByName(
            'reports.shared-saved-views.copy'
        );

        $this->assertNotNull($route);
        $this->assertContains(
            'POST',
            $route->methods()
        );
        $this->assertContains(
            'auth',
            $route->gatherMiddleware()
        );
    }

    public function test_recipient_copy_is_independent_active_and_non_default(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $source = $this->savedView(
            $owner,
            'Shared Source',
            [
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
            ],
            true
        );

        $share = app(
            ReportSavedViewShareService::class
        )->share(
            $owner,
            $source,
            $recipient,
            'view'
        );

        $this->actingAs($recipient)
            ->post(
                route(
                    'reports.shared-saved-views.copy',
                    $share
                )
            )
            ->assertRedirect(
                route('reports.saved-views.index')
            );

        $copy = ReportSavedView::query()
            ->where('user_id', $recipient->id)
            ->firstOrFail();

        $this->assertNotSame(
            $source->id,
            $copy->id
        );
        $this->assertSame(
            $source->report_key,
            $copy->report_key
        );
        $this->assertSame(
            $source->filters,
            $copy->filters
        );
        $this->assertFalse(
            $copy->is_default
        );
        $this->assertNull(
            $copy->archived_at
        );
        $this->assertTrue(
            $copy->isActive()
        );
    }

    public function test_copy_does_not_copy_owner_tags_or_shares(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $otherRecipient = User::factory()->create();

        $source = $this->savedView(
            $owner,
            'Tagged Shared Source'
        );

        $tag = ReportSavedViewTag::query()->create([
            'user_id' => $owner->id,
            'name' => 'Owner Tag',
            'normalized_name' => 'owner tag',
            'color' => '#64748B',
        ]);

        $source->tags()->attach($tag->id);

        $service = app(
            ReportSavedViewShareService::class
        );

        $share = $service->share(
            $owner,
            $source,
            $recipient,
            'use'
        );

        $service->share(
            $owner,
            $source,
            $otherRecipient,
            'view'
        );

        $copy = $service->copyToRecipient(
            $recipient,
            $share
        );

        $this->assertFalse(
            $copy->tags()->exists()
        );
        $this->assertFalse(
            $copy->shares()->exists()
        );
        $this->assertSame(
            2,
            ReportSavedViewShare::query()
                ->where(
                    'report_saved_view_id',
                    $source->id
                )
                ->count()
        );
    }

    public function test_archived_source_is_hidden_and_restore_reactivates_share(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $source = $this->savedView(
            $owner,
            'Archive Boundary'
        );

        $share = app(
            ReportSavedViewShareService::class
        )->share(
            $owner,
            $source,
            $recipient,
            'use'
        );

        $source->forceFill([
            'archived_at' => now(),
        ])->save();

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.index'
                )
            )
            ->assertOk()
            ->assertDontSee('Archive Boundary');

        $this->actingAs($recipient)
            ->post(
                route(
                    'reports.shared-saved-views.copy',
                    $share
                )
            )
            ->assertNotFound();

        $this->assertDatabaseHas(
            'report_saved_view_shares',
            ['id' => $share->id]
        );

        $source->forceFill([
            'archived_at' => null,
        ])->save();

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.index'
                )
            )
            ->assertOk()
            ->assertSee('Archive Boundary');
    }

    public function test_permanent_delete_cascades_share(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $source = $this->savedView(
            $owner,
            'Delete Boundary'
        );

        $share = app(
            ReportSavedViewShareService::class
        )->share(
            $owner,
            $source,
            $recipient,
            'view'
        );

        $source->delete();

        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $share->id]
        );
    }

    public function test_csv_writer_parser_and_version_sources_do_not_reference_shares(): void
    {
        foreach ([
            app_path(
                'Support/Reports/ReportSavedViewCsvExportWriter.php'
            ),
            app_path(
                'Support/Reports/ReportSavedViewCsvImportParser.php'
            ),
            base_path(
                'docs/phase-73a-saved-view-import-export-format-version-contract.json'
            ),
        ] as $path) {
            $this->assertFileExists($path);

            $contents = file_get_contents($path);

            $this->assertIsString($contents);
            $this->assertStringNotContainsString(
                'report_saved_view_shares',
                $contents
            );
            $this->assertStringNotContainsString(
                'recipient_user_id',
                $contents
            );
            $this->assertStringNotContainsString(
                'owner_user_id',
                $contents
            );
        }
    }

    public function test_imported_or_new_recipient_copy_has_no_shares_by_default(): void
    {
        $user = User::factory()->create();

        $savedView = $this->savedView(
            $user,
            'Private New Row'
        );

        $this->assertDatabaseCount(
            'report_saved_view_shares',
            0
        );
        $this->assertFalse(
            $savedView->shares()->exists()
        );
    }

    private function savedView(
        User $owner,
        string $name,
        array $filters = [],
        bool $isDefault = false
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => $filters,
            'is_default' => $isDefault,
        ]);
    }
}
