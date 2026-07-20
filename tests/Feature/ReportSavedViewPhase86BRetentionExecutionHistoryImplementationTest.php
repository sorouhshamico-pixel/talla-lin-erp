<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivity;
use App\Models\ReportSavedViewShareActivityRetentionExecution;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

class ReportSavedViewPhase86BRetentionExecutionHistoryImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_table_model_and_constants_are_available(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_share_activity_retention_executions'
            )
        );

        $this->assertSame(
            'manual_preview',
            ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_PREVIEW
        );
        $this->assertSame(
            'manual_execution',
            ReportSavedViewShareActivityRetentionExecution::TYPE_MANUAL_EXECUTION
        );
        $this->assertSame(
            'command_execution',
            ReportSavedViewShareActivityRetentionExecution::TYPE_COMMAND_EXECUTION
        );
        $this->assertSame(
            'succeeded',
            ReportSavedViewShareActivityRetentionExecution::STATUS_SUCCEEDED
        );
    }

    public function test_manual_preview_and_execution_are_recorded(): void
    {
        Carbon::setTestNow('2026-07-20 12:00:00');

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );

        $user = User::factory()->create();

        ReportSavedViewShareActivity::query()->create([
            'action' => ReportSavedViewShareActivity::ACTION_SHARED,
            'source_name_snapshot' => 'History Test',
            'source_report_key_snapshot' => 'profit-loss',
            'metadata' => null,
            'created_at' => '2026-05-01 00:00:00',
        ]);

        $this->actingAs($user)
            ->postJson(
                route(
                    'reports.saved-view-share-activity-retention.preview'
                ),
                ['days' => 30]
            )
            ->assertOk();

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
            ->assertOk();

        $this->assertDatabaseHas(
            'report_saved_view_share_activity_retention_executions',
            [
                'type' => 'manual_preview',
                'status' => 'succeeded',
                'actor_user_id' => $user->id,
                'requested_days' => 30,
            ]
        );

        $this->assertDatabaseHas(
            'report_saved_view_share_activity_retention_executions',
            [
                'type' => 'manual_execution',
                'status' => 'succeeded',
                'actor_user_id' => $user->id,
                'requested_chunk_size' => 1,
            ]
        );
    }

    public function test_command_execution_is_recorded(): void
    {
        Carbon::setTestNow('2026-07-20 12:00:00');

        $exit = Artisan::call(
            'reports:prune-saved-view-share-activities',
            [
                '--days' => 30,
                '--dry-run' => true,
            ]
        );

        $this->assertSame(0, $exit);

        $this->assertDatabaseHas(
            'report_saved_view_share_activity_retention_executions',
            [
                'type' => 'command_execution',
                'status' => 'succeeded',
                'requested_days' => 30,
            ]
        );
    }

    public function test_history_route_is_protected_and_paginated(): void
    {
        $route = Route::getRoutes()->getByName(
            'reports.saved-view-share-activity-retention.history'
        );

        $this->assertNotNull($route);
        $this->assertContains('auth', $route->gatherMiddleware());
        $this->assertContains(
            'can:manage_saved_view_share_activity_retention',
            $route->gatherMiddleware()
        );

        Gate::define(
            'manage_saved_view_share_activity_retention',
            fn (User $user): bool => true
        );

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(
                route(
                    'reports.saved-view-share-activity-retention.history',
                    ['per_page' => 500]
                )
            )
            ->assertOk()
            ->assertJsonPath('per_page', 100);
    }

    public function test_rows_are_immutable(): void
    {
        $row = ReportSavedViewShareActivityRetentionExecution::query()
            ->create([
                'type' => 'manual_preview',
                'status' => 'succeeded',
                'requested_days' => 30,
                'started_at' => now(),
                'finished_at' => now(),
            ]);

        $this->expectException(LogicException::class);

        $row->update([
            'status' => 'failed',
        ]);
    }
}
