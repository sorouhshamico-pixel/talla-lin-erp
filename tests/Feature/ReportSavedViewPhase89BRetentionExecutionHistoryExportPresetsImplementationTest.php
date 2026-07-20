<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReportSavedViewPhase89BRetentionExecutionHistoryExportPresetsImplementationTest
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

    public function test_authorized_user_sees_all_fixed_presets(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index'
            ));

        $response
            ->assertOk()
            ->assertSee('Presets')
            ->assertSee('All executions')
            ->assertSee('Failed executions')
            ->assertSee('Conflicted executions')
            ->assertSee('Manual executions')
            ->assertSee('Scheduled executions')
            ->assertSee('Command executions');
    }

    public function test_preset_links_target_existing_administration_route_with_filters(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index'
            ));

        foreach ([
            [
                'preset' => 'all',
            ],
            [
                'preset' => 'failed',
                'status' => 'failed',
            ],
            [
                'preset' => 'conflicted',
                'status' => 'conflicted',
            ],
            [
                'preset' => 'manual',
                'type' => 'manual_execution',
            ],
            [
                'preset' => 'scheduled',
                'type' => 'scheduled_execution',
            ],
            [
                'preset' => 'command',
                'type' => 'command_execution',
            ],
        ] as $parameters) {
            $response->assertSee(
                route(
                    'reports.saved-view-share-activity-retention.index',
                    $parameters
                )
            );
        }
    }

    public function test_active_preset_is_marked_without_javascript(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index',
                [
                    'preset' => 'failed',
                    'status' => 'failed',
                ]
            ));

        $response
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('value="failed"', false);
    }

    public function test_presets_do_not_trigger_export_or_add_persistence(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/reports/saved-views/'
                . 'share-activity-retention.blade.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "'reports.saved-view-share-activity-retention.index'",
            $source
        );
        $this->assertStringContainsString(
            "'filters' => []",
            $source
        );
        $this->assertStringNotContainsString(
            '<script',
            $source
        );
        $this->assertStringNotContainsString(
            'localStorage',
            $source
        );
        $this->assertStringNotContainsString(
            'sessionStorage',
            $source
        );
        $this->assertStringNotContainsString(
            'Cache::',
            $source
        );
        $this->assertStringNotContainsString(
            'DB::',
            $source
        );
    }

    public function test_contract_keeps_controller_service_routes_and_database_unchanged(): void
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-89a-retention-execution-history-export-presets-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        $planned = $document['presets_contract']
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
        $this->assertFalse(
            $planned['database_changes_expected']
        );
    }
}
