<?php

namespace Tests\Feature;

use App\Models\ReportSavedViewShareActivity;
use App\Services\ReportSavedViewShareActivityRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class ReportSavedViewPhase84BSharingActivityRetentionImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_default_configuration_retains_forever(): void
    {
        $this->assertFalse(
            config(
                'reports.saved_view_share_activity_retention.enabled'
            )
        );
        $this->assertNull(
            config(
                'reports.saved_view_share_activity_retention.days'
            )
        );
        $this->assertSame(
            500,
            config(
                'reports.saved_view_share_activity_retention.chunk_size'
            )
        );
        $this->assertSame(
            'daily',
            config(
                'reports.saved_view_share_activity_retention.schedule'
            )
        );
    }

    public function test_preview_counts_without_deleting(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');

        $old = $this->activityAt('2026-05-01 00:00:00');
        $recent = $this->activityAt('2026-07-01 00:00:00');

        $result = app(
            ReportSavedViewShareActivityRetentionService::class
        )->preview(30);

        $this->assertTrue($result['dry_run']);
        $this->assertSame(1, $result['candidate_count']);
        $this->assertSame(0, $result['deleted_count']);

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $old->id]
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $recent->id]
        );
    }

    public function test_prune_deletes_only_rows_older_than_cutoff(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');

        $old = $this->activityAt('2026-05-01 00:00:00');
        $atCutoff = $this->activityAt('2026-06-19 12:00:00');
        $future = $this->activityAt('2026-08-01 00:00:00');

        $result = app(
            ReportSavedViewShareActivityRetentionService::class
        )->prune(30, 1);

        $this->assertFalse($result['dry_run']);
        $this->assertSame(1, $result['deleted_count']);

        $this->assertDatabaseMissing(
            'report_saved_view_share_activities',
            ['id' => $old->id]
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $atCutoff->id]
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $future->id]
        );
    }

    public function test_invalid_days_and_chunks_are_rejected(): void
    {
        $service = app(
            ReportSavedViewShareActivityRetentionService::class
        );

        foreach ([29, 3651] as $days) {
            try {
                $service->preview($days);
                $this->fail(
                    'Expected invalid retention days.'
                );
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString(
                    'between 30 and 3650',
                    $exception->getMessage()
                );
            }
        }

        foreach ([0, 10001] as $chunk) {
            try {
                $service->prune(30, $chunk);
                $this->fail(
                    'Expected invalid chunk size.'
                );
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString(
                    'between 1 and 10000',
                    $exception->getMessage()
                );
            }
        }
    }

    public function test_command_dry_run_and_execution_work(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');

        $old = $this->activityAt('2026-05-01 00:00:00');

        $dryRunExit = Artisan::call(
            'reports:prune-saved-view-share-activities',
            [
                '--days' => 30,
                '--dry-run' => true,
            ]
        );

        $this->assertSame(0, $dryRunExit);
        $this->assertStringContainsString(
            'Candidates',
            Artisan::output()
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $old->id]
        );

        $executeExit = Artisan::call(
            'reports:prune-saved-view-share-activities',
            [
                '--days' => 30,
                '--chunk' => 1,
            ]
        );

        $this->assertSame(0, $executeExit);
        $this->assertStringContainsString(
            'Deleted',
            Artisan::output()
        );
        $this->assertDatabaseMissing(
            'report_saved_view_share_activities',
            ['id' => $old->id]
        );
    }

    public function test_command_requires_explicit_or_configured_days(): void
    {
        config()->set(
            'reports.saved_view_share_activity_retention.days',
            null
        );

        $exit = Artisan::call(
            'reports:prune-saved-view-share-activities'
        );

        $this->assertSame(1, $exit);
        $this->assertStringContainsString(
            'Retention days are not configured',
            Artisan::output()
        );
    }

    public function test_pruning_does_not_create_activity_rows(): void
    {
        Carbon::setTestNow('2026-07-19 12:00:00');

        $this->activityAt('2026-05-01 00:00:00');

        app(
            ReportSavedViewShareActivityRetentionService::class
        )->prune(30);

        $this->assertSame(
            0,
            DB::table(
                'report_saved_view_share_activities'
            )->count()
        );
    }

    private function activityAt(
        string $createdAt
    ): ReportSavedViewShareActivity {
        return ReportSavedViewShareActivity::query()
            ->create([
                'action' =>
                    ReportSavedViewShareActivity::ACTION_SHARED,
                'source_name_snapshot' => 'Retention Test',
                'source_report_key_snapshot' => 'profit-loss',
                'metadata' => null,
                'created_at' => $createdAt,
            ]);
    }
}
