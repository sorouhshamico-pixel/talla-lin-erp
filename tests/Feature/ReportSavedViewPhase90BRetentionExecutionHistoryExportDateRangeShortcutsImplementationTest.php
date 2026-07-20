<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReportSavedViewPhase90BRetentionExecutionHistoryExportDateRangeShortcutsImplementationTest
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

        Carbon::setTestNow(
            Carbon::parse('2026-07-20 12:34:00', 'UTC')
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_authorized_user_sees_all_date_range_shortcuts(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index'
            ));

        $response
            ->assertOk()
            ->assertSee('Date range shortcuts')
            ->assertSee('Today')
            ->assertSee('Last 7 days')
            ->assertSee('Last 30 days')
            ->assertSee('This month')
            ->assertSee('Previous month')
            ->assertSee('Clear date range');
    }

    public function test_shortcut_links_use_locked_utc_boundaries(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index'
            ));

        foreach ([
            [
                'date_shortcut' => 'today',
                'started_from' => '2026-07-20T00:00',
                'started_to' => '2026-07-20T23:59',
            ],
            [
                'date_shortcut' => 'last_7_days',
                'started_from' => '2026-07-13T12:34',
                'started_to' => '2026-07-20T12:34',
            ],
            [
                'date_shortcut' => 'last_30_days',
                'started_from' => '2026-06-20T12:34',
                'started_to' => '2026-07-20T12:34',
            ],
            [
                'date_shortcut' => 'this_month',
                'started_from' => '2026-07-01T00:00',
                'started_to' => '2026-07-31T23:59',
            ],
            [
                'date_shortcut' => 'previous_month',
                'started_from' => '2026-06-01T00:00',
                'started_to' => '2026-06-30T23:59',
            ],
            [
                'date_shortcut' => 'clear_dates',
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

    public function test_shortcuts_preserve_non_date_filters(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index',
                [
                    'preset' => 'failed',
                    'type' => 'manual_execution',
                    'status' => 'failed',
                    'actor_user_id' => 42,
                ]
            ));

        $response->assertSee(
            route(
                'reports.saved-view-share-activity-retention.index',
                [
                    'preset' => 'failed',
                    'type' => 'manual_execution',
                    'status' => 'failed',
                    'actor_user_id' => 42,
                    'date_shortcut' => 'today',
                    'started_from' => '2026-07-20T00:00',
                    'started_to' => '2026-07-20T23:59',
                ]
            )
        );
    }

    public function test_active_shortcut_is_marked_and_manual_dates_remain_editable(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route(
                'reports.saved-view-share-activity-retention.index',
                [
                    'date_shortcut' => 'last_7_days',
                    'started_from' => '2026-07-13T12:34',
                    'started_to' => '2026-07-20T12:34',
                ]
            ));

        $response
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('value="2026-07-13T12:34"', false)
            ->assertSee('value="2026-07-20T12:34"', false);
    }

    public function test_shortcuts_require_no_backend_or_persistence_changes(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/reports/saved-views/'
                . 'share-activity-retention.blade.php'
            )
        );

        $this->assertIsString($source);
        $this->assertStringContainsString(
            "Carbon::now('UTC')",
            $source
        );
        $this->assertStringContainsString(
            "'date_shortcut' => \$shortcutKey",
            $source
        );
        $this->assertStringNotContainsString('<script', $source);
        $this->assertStringNotContainsString('localStorage', $source);
        $this->assertStringNotContainsString('sessionStorage', $source);
        $this->assertStringNotContainsString('Cache::', $source);
        $this->assertStringNotContainsString('DB::', $source);
    }
}
