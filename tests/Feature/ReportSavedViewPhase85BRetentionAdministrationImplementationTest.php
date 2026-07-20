<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityRetentionAdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase85BRetentionAdministrationImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_routes_require_authentication_and_permission(): void
    {
        foreach ([
            'reports.saved-view-share-activity-retention.index',
            'reports.saved-view-share-activity-retention.preview',
            'reports.saved-view-share-activity-retention.execute',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
            $this->assertContains(
                'can:manage_saved_view_share_activity_retention',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_permission_gate_is_owner_based(): void
    {
        $providerSource = file_get_contents(
            app_path('Providers/AppServiceProvider.php')
        );

        $this->assertIsString($providerSource);
        $this->assertStringContainsString(
            "'manage_saved_view_share_activity_retention'",
            $providerSource
        );
        $this->assertStringContainsString(
            '$user->isOwner()',
            $providerSource
        );
    }

    public function test_authenticated_user_is_forbidden_when_gate_denies(): void
    {
        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => false
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(
                route(
                    'reports.saved-view-share-activity-retention.index'
                )
            )
            ->assertForbidden();
    }

    public function test_authenticated_user_can_view_status_when_gate_allows(): void
    {
        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(
                route(
                    'reports.saved-view-share-activity-retention.index'
                )
            )
            ->assertOk()
            ->assertJsonStructure([
                'retention_enabled',
                'retention_days',
                'chunk_size',
                'schedule',
                'candidate_count',
                'oldest_activity_at',
                'newest_activity_at',
                'last_manual_preview',
                'last_manual_execution',
            ]);
    }

    public function test_preview_never_deletes_and_is_audited(): void
    {
        Carbon::setTestNow('2026-07-20 12:00:00');

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );

        $user = User::factory()->create();

        $activity = $this->activityAt(
            '2026-05-01 00:00:00'
        );

        $this->actingAs($user)
            ->postJson(
                route(
                    'reports.saved-view-share-activity-retention.preview'
                ),
                ['days' => 30]
            )
            ->assertOk()
            ->assertJson([
                'dry_run' => true,
                'candidate_count' => 1,
                'deleted_count' => 0,
                'actor_user_id' => $user->id,
                'requested_days' => 30,
            ]);

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $activity->id]
        );

        $this->assertSame(
            $user->id,
            Cache::get(
                'saved-view-share-activity-retention:last-preview'
            )['actor_user_id']
        );
    }

    public function test_execution_deletes_and_is_audited(): void
    {
        Carbon::setTestNow('2026-07-20 12:00:00');

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );

        $user = User::factory()->create();

        $activity = $this->activityAt(
            '2026-05-01 00:00:00'
        );

        $this->actingAs($user)
            ->postJson(
                route(
                    'reports.saved-view-share-activity-retention.execute'
                ),
                [
                    'days' => 30,
                    'chunk_size' => 1,
                    'confirmation' => 'PRUNE',
                ]
            )
            ->assertOk()
            ->assertJson([
                'dry_run' => false,
                'deleted_count' => 1,
                'actor_user_id' => $user->id,
                'requested_days' => 30,
                'requested_chunk_size' => 1,
            ]);

        $this->assertDatabaseMissing(
            'report_saved_view_share_activities',
            ['id' => $activity->id]
        );

        $this->assertSame(
            $user->id,
            Cache::get(
                'saved-view-share-activity-retention:last-execution'
            )['actor_user_id']
        );
    }

    public function test_execution_returns_conflict_when_lock_is_held(): void
    {
        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );

        $user = User::factory()->create();

        $lock = Cache::lock(
            ReportSavedViewShareActivityRetentionAdminService::LOCK_NAME,
            60
        );

        $this->assertTrue($lock->get());

        try {
            $this->actingAs($user)
                ->postJson(
                    route(
                        'reports.saved-view-share-activity-retention.execute'
                    ),
                    [
                        'days' => 30,
                        'chunk_size' => 500,
                        'confirmation' => 'PRUNE',
                    ]
                )
                ->assertConflict();
        } finally {
            $lock->release();
        }
    }

    public function test_validation_contract_is_declared_in_controller(): void
    {
        $controllerSource = file_get_contents(
            app_path(
                'Http/Controllers/'
                . 'ReportSavedViewShareActivityRetentionAdminController.php'
            )
        );

        $this->assertIsString($controllerSource);

        foreach ([
            "'required'",
            "'integer'",
            "'min:30'",
            "'max:3650'",
            "'min:1'",
            "'max:10000'",
            "'in:PRUNE'",
        ] as $rule) {
            $this->assertStringContainsString(
                $rule,
                $controllerSource
            );
        }
    }

    public function test_status_view_is_read_only(): void
    {
        $viewSource = file_get_contents(
            resource_path(
                'views/reports/saved-views/'
                . 'share-activity-retention.blade.php'
            )
        );

        $this->assertIsString($viewSource);
        $this->assertStringContainsString(
            'Retention configuration is read-only',
            $viewSource
        );
        $this->assertStringNotContainsString(
            '<form',
            $viewSource
        );
    }

    private function activityAt(
        string $createdAt
    ): ReportSavedViewShareActivity {
        return ReportSavedViewShareActivity::query()
            ->create([
                'action' =>
                    ReportSavedViewShareActivity::ACTION_SHARED,
                'source_name_snapshot' => 'Admin Test',
                'source_report_key_snapshot' => 'profit-loss',
                'metadata' => null,
                'created_at' => $createdAt,
            ]);
    }
}
