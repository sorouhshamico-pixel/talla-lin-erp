<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReportSavedViewPhase88BRetentionExecutionHistoryExportAdministrationImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );
    }

    public function test_authorized_user_sees_export_administration_controls(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index'
            ));

        $response
            ->assertOk()
            ->assertSee('Retention execution history export')
            ->assertSee('Export CSV')
            ->assertSee('Export JSON')
            ->assertSee('Clear filters')
            ->assertSee('CSV is limited to 100000 rows')
            ->assertSee('JSON is limited to 10000 rows')
            ->assertSee('context and updated_at are excluded')
            ->assertSee(
                route(
                    'reports.saved-view-share-activity-retention.history.export.csv'
                ),
                false
            )
            ->assertSee(
                route(
                    'reports.saved-view-share-activity-retention.history.export.json'
                ),
                false
            );
    }

    public function test_filter_values_are_rendered_for_query_string_forwarding(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index',
                [
                    'type' => 'manual_execution',
                    'status' => 'failed',
                    'actor_user_id' => 42,
                    'started_from' => '2026-07-01T08:00',
                    'started_to' => '2026-07-20T18:00',
                ]
            ));

        $response
            ->assertOk()
            ->assertSee('value="manual_execution"', false)
            ->assertSee('value="failed"', false)
            ->assertSee('value="42"', false)
            ->assertSee('value="2026-07-01T08:00"', false)
            ->assertSee('value="2026-07-20T18:00"', false);
    }

    public function test_view_uses_get_only_and_reuses_existing_routes(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/reports/saved-views/'
                . 'share-activity-retention.blade.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            'method="GET"',
            $source
        );
        $this->assertStringContainsString(
            "route('reports.saved-view-share-activity-retention.history.export.csv')",
            $source
        );
        $this->assertStringContainsString(
            "route('reports.saved-view-share-activity-retention.history.export.json')",
            $source
        );
        $this->assertStringContainsString(
            "route('reports.saved-view-share-activity-retention.index')",
            $source
        );
        $this->assertStringNotContainsString(
            'method="POST"',
            $source
        );
        $this->assertStringNotContainsString(
            '@csrf',
            $source
        );
        $this->assertStringNotContainsString(
            'context[',
            $source
        );
    }

    public function test_phase_does_not_add_controller_service_or_route_files(): void
    {
        $contract = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-88a-retention-execution-history-export-administration-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($contract);
        $planned = $contract['administration_contract']
            ['planned_implementation'];

        $this->assertFalse(
            $planned['controller_changes_expected']
        );
        $this->assertFalse(
            $planned['service_changes_expected']
        );
        $this->assertFalse(
            $planned['route_changes_expected']
        );
    }
}
