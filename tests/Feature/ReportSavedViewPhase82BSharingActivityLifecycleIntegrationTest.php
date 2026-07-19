<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use App\Services\ReportSavedViewService;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase82BSharingActivityLifecycleIntegrationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_archive_records_one_activity_per_existing_share(): void
    {
        [$owner, $savedView, $recipients] =
            $this->sharedFixture(2);

        $before = ReportSavedViewShareActivity::query()->count();

        $this->assertTrue(
            $this->savedViewService()->archive(
                $owner,
                $savedView->id
            )
        );

        $activities = ReportSavedViewShareActivity::query()
            ->where('action', 'source_archived')
            ->orderBy('recipient_user_id')
            ->get();

        $this->assertCount(2, $activities);
        $this->assertSame(
            $before + 2,
            ReportSavedViewShareActivity::query()->count()
        );
        $this->assertEqualsCanonicalizing(
            collect($recipients)->pluck('id')->all(),
            $activities->pluck('recipient_user_id')->all()
        );
    }

    public function test_repeat_archive_records_no_duplicate_activity(): void
    {
        [$owner, $savedView] = $this->sharedFixture(1);
        $service = $this->savedViewService();

        $this->assertTrue($service->archive($owner, $savedView->id));

        $count = ReportSavedViewShareActivity::query()->count();

        $this->assertFalse($service->archive($owner, $savedView->id));
        $this->assertSame(
            $count,
            ReportSavedViewShareActivity::query()->count()
        );
    }

    public function test_restore_records_activity_and_reactivates_access(): void
    {
        [$owner, $savedView, $recipients] =
            $this->sharedFixture(1);

        $recipient = $recipients[0];
        $service = $this->savedViewService();

        $service->archive($owner, $savedView->id);

        $this->assertTrue(
            app(ReportSavedViewShareService::class)
                ->listReceived($recipient)
                ->isEmpty()
        );

        $this->assertTrue($service->restore($owner, $savedView->id));

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'report_saved_view_id' => $savedView->id,
                'recipient_user_id' => $recipient->id,
                'action' => 'source_restored',
            ]
        );

        $this->assertCount(
            1,
            app(ReportSavedViewShareService::class)
                ->listReceived($recipient)
        );
    }

    public function test_bulk_archive_and_restore_record_only_changed_sources(): void
    {
        $owner = User::factory()->create();

        [$first, $firstRecipient] =
            $this->sharedViewForOwner($owner, 'Bulk First');
        [$second, $secondRecipient] =
            $this->sharedViewForOwner($owner, 'Bulk Second');
        [$alreadyArchived] =
            $this->sharedViewForOwner($owner, 'Already Archived');

        $alreadyArchived->forceFill([
            'archived_at' => now(),
        ])->save();

        $service = $this->savedViewService();

        $this->assertSame(
            2,
            $service->bulkArchive(
                $owner,
                [
                    $first->id,
                    $second->id,
                    $alreadyArchived->id,
                ]
            )
        );

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'report_saved_view_id' => $first->id,
                'recipient_user_id' => $firstRecipient->id,
                'action' => 'source_archived',
            ]
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'report_saved_view_id' => $second->id,
                'recipient_user_id' => $secondRecipient->id,
                'action' => 'source_archived',
            ]
        );
        $this->assertDatabaseMissing(
            'report_saved_view_share_activities',
            [
                'report_saved_view_id' => $alreadyArchived->id,
                'action' => 'source_archived',
            ]
        );

        $this->assertSame(
            3,
            $service->bulkRestore(
                $owner,
                [
                    $first->id,
                    $second->id,
                    $alreadyArchived->id,
                ]
            )
        );

        $this->assertSame(
            3,
            ReportSavedViewShareActivity::query()
                ->where('action', 'source_restored')
                ->count()
        );
    }

    public function test_permanent_delete_records_activity_before_cascade(): void
    {
        [$owner, $savedView, $recipients] =
            $this->sharedFixture(1);

        $recipient = $recipients[0];
        $savedViewId = $savedView->id;

        $this->savedViewService()->delete($owner, $savedViewId);

        $this->assertDatabaseMissing(
            'report_saved_views',
            ['id' => $savedViewId]
        );

        $activity = ReportSavedViewShareActivity::query()
            ->where('action', 'source_deleted')
            ->sole();

        $this->assertNull($activity->report_saved_view_id);
        $this->assertNull($activity->report_saved_view_share_id);
        $this->assertSame(
            $recipient->id,
            $activity->recipient_user_id
        );
        $this->assertSame(
            'Lifecycle Shared View',
            $activity->source_name_snapshot
        );
        $this->assertSame(
            'profit-loss',
            $activity->source_report_key_snapshot
        );
    }

    public function test_delete_for_report_records_each_shared_source(): void
    {
        $owner = User::factory()->create();

        [$first] = $this->sharedViewForOwner(
            $owner,
            'Delete Report First',
            'profit-loss'
        );
        [$second] = $this->sharedViewForOwner(
            $owner,
            'Delete Report Second',
            'profit-loss'
        );
        [$other] = $this->sharedViewForOwner(
            $owner,
            'Keep Other Report',
            'sales-register'
        );

        $this->savedViewService()->deleteForReport(
            $owner,
            'profit-loss'
        );

        $this->assertDatabaseMissing(
            'report_saved_views',
            ['id' => $first->id]
        );
        $this->assertDatabaseMissing(
            'report_saved_views',
            ['id' => $second->id]
        );
        $this->assertDatabaseHas(
            'report_saved_views',
            ['id' => $other->id]
        );

        $this->assertSame(
            2,
            ReportSavedViewShareActivity::query()
                ->where('action', 'source_deleted')
                ->count()
        );
    }

    public function test_unshared_sources_create_no_sharing_lifecycle_activity(): void
    {
        $owner = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => 'Private Source',
            'filters' => [],
            'is_default' => false,
        ]);

        $service = $this->savedViewService();

        $this->assertTrue($service->archive($owner, $savedView->id));
        $this->assertTrue($service->restore($owner, $savedView->id));

        $service->delete($owner, $savedView->id);

        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            0
        );
    }

    public function test_foreign_and_noop_operations_record_nothing(): void
    {
        [$owner, $savedView] = $this->sharedFixture(1);

        $foreign = User::factory()->create();
        $before = ReportSavedViewShareActivity::query()->count();

        $this->assertFalse(
            $this->savedViewService()->archive(
                $foreign,
                $savedView->id
            )
        );

        $this->savedViewService()->delete(
            $foreign,
            $savedView->id
        );

        $this->assertSame(
            $before,
            ReportSavedViewShareActivity::query()->count()
        );
        $this->assertDatabaseHas(
            'report_saved_views',
            ['id' => $savedView->id]
        );
    }

    private function savedViewService(): ReportSavedViewService
    {
        return app(ReportSavedViewService::class);
    }

    private function sharedFixture(int $recipientCount): array
    {
        $owner = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => 'Lifecycle Shared View',
            'filters' => [],
            'is_default' => false,
        ]);

        $recipients = [];

        for ($index = 0; $index < $recipientCount; $index++) {
            $recipient = User::factory()->create();
            $recipients[] = $recipient;

            app(ReportSavedViewShareService::class)
                ->share(
                    $owner,
                    $savedView,
                    $recipient,
                    $index % 2 === 0 ? 'view' : 'use'
                );
        }

        return [$owner, $savedView, $recipients];
    }

    private function sharedViewForOwner(
        User $owner,
        string $name,
        string $reportKey = 'profit-loss'
    ): array {
        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => $reportKey,
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);

        $recipient = User::factory()->create();

        app(ReportSavedViewShareService::class)
            ->share(
                $owner,
                $savedView,
                $recipient,
                'view'
            );

        return [$savedView, $recipient];
    }
}
