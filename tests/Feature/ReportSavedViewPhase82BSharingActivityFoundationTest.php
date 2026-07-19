<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityService;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class ReportSavedViewPhase82BSharingActivityFoundationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_activity_schema_and_model_contract_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_share_activities'
            )
        );

        foreach ([
            'report_saved_view_share_id',
            'report_saved_view_id',
            'actor_user_id',
            'owner_user_id',
            'recipient_user_id',
            'action',
            'permission_before',
            'permission_after',
            'source_name_snapshot',
            'source_report_key_snapshot',
            'metadata',
            'created_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn(
                    'report_saved_view_share_activities',
                    $column
                ),
                $column
            );
        }

        $activity = new ReportSavedViewShareActivity();

        foreach ([
            'share',
            'savedView',
            'actor',
            'owner',
            'recipient',
        ] as $method) {
            $this->assertTrue(
                method_exists($activity, $method)
            );
        }

        $this->assertSame(
            [
                'shared',
                'permission_updated',
                'revoked',
                'applied',
                'copied',
                'source_archived',
                'source_restored',
                'source_deleted',
            ],
            ReportSavedViewShareActivity::ACTIONS
        );
    }

    public function test_writer_records_complete_snapshot_context(): void
    {
        [$owner, $recipient, $savedView, $share] =
            $this->sharingFixture();

        $activity = app(
            ReportSavedViewShareActivityService::class
        )->record(
            ReportSavedViewShareActivity::ACTION_SHARED,
            $owner,
            $owner,
            $recipient,
            $savedView,
            $share,
            null,
            'view',
            ['channel' => 'owner-ui']
        );

        $this->assertSame(
            $share->id,
            $activity->report_saved_view_share_id
        );
        $this->assertSame(
            $savedView->id,
            $activity->report_saved_view_id
        );
        $this->assertSame(
            $owner->id,
            $activity->actor_user_id
        );
        $this->assertSame(
            $owner->id,
            $activity->owner_user_id
        );
        $this->assertSame(
            $recipient->id,
            $activity->recipient_user_id
        );
        $this->assertSame(
            'shared',
            $activity->action
        );
        $this->assertNull(
            $activity->permission_before
        );
        $this->assertSame(
            'view',
            $activity->permission_after
        );
        $this->assertSame(
            'Foundation Shared View',
            $activity->source_name_snapshot
        );
        $this->assertSame(
            'profit-loss',
            $activity->source_report_key_snapshot
        );
        $this->assertSame(
            ['channel' => 'owner-ui'],
            $activity->metadata
        );
        $this->assertNotNull(
            $activity->created_at
        );
    }

    public function test_writer_accepts_deleted_reference_safe_context(): void
    {
        $actor = User::factory()->create();

        $activity = app(
            ReportSavedViewShareActivityService::class
        )->record(
            ReportSavedViewShareActivity::ACTION_SOURCE_DELETED,
            $actor,
            null,
            null,
            null,
            null,
            null,
            null,
            ['source_id_snapshot' => 999]
        );

        $this->assertNull(
            $activity->report_saved_view_share_id
        );
        $this->assertNull(
            $activity->report_saved_view_id
        );
        $this->assertSame(
            $actor->id,
            $activity->actor_user_id
        );
        $this->assertSame(
            ['source_id_snapshot' => 999],
            $activity->metadata
        );
    }

    public function test_invalid_action_and_permission_are_rejected(): void
    {
        $service = app(
            ReportSavedViewShareActivityService::class
        );

        try {
            $service->record('invalid-action');
            $this->fail(
                'Invalid action should throw.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Invalid sharing activity action.',
                $exception->getMessage()
            );
        }

        try {
            $service->record(
                ReportSavedViewShareActivity::ACTION_SHARED,
                permissionAfter: 'manage'
            );
            $this->fail(
                'Invalid permission should throw.'
            );
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'Invalid sharing activity permission.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            0
        );
    }

    public function test_activity_records_cannot_be_updated_or_deleted(): void
    {
        $activity = app(
            ReportSavedViewShareActivityService::class
        )->record(
            ReportSavedViewShareActivity::ACTION_SHARED
        );

        try {
            $activity->forceFill([
                'action' =>
                    ReportSavedViewShareActivity::ACTION_REVOKED,
            ])->save();

            $this->fail(
                'Activity update should throw.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Sharing activity records are immutable.',
                $exception->getMessage()
            );
        }

        try {
            $activity->delete();

            $this->fail(
                'Activity delete should throw.'
            );
        } catch (LogicException $exception) {
            $this->assertSame(
                'Sharing activity records are immutable.',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'id' => $activity->id,
                'action' => 'shared',
            ]
        );
    }

    public function test_foreign_keys_set_null_and_activity_survives_deletion(): void
    {
        [$owner, $recipient, $savedView, $share] =
            $this->sharingFixture();

        $activity = app(
            ReportSavedViewShareActivityService::class
        )->record(
            ReportSavedViewShareActivity::ACTION_SHARED,
            $owner,
            $owner,
            $recipient,
            $savedView,
            $share,
            null,
            'view'
        );

        $shareId = $share->id;
        $savedViewId = $savedView->id;

        $share->delete();
        $savedView->delete();
        $owner->delete();
        $recipient->delete();

        $activity->refresh();

        $this->assertNull(
            $activity->report_saved_view_share_id
        );
        $this->assertNull(
            $activity->report_saved_view_id
        );
        $this->assertNull(
            $activity->actor_user_id
        );
        $this->assertNull(
            $activity->owner_user_id
        );
        $this->assertNull(
            $activity->recipient_user_id
        );
        $this->assertSame(
            'Foundation Shared View',
            $activity->source_name_snapshot
        );
        $this->assertSame(
            'profit-loss',
            $activity->source_report_key_snapshot
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['id' => $activity->id]
        );
        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $shareId]
        );
        $this->assertDatabaseMissing(
            'report_saved_views',
            ['id' => $savedViewId]
        );
    }

    public function test_stage_two_integrates_successful_share_activity(): void
    {
        [$owner, $recipient, $savedView] =
            $this->sharingFixture(
                createShare: false
            );

        $share = app(
            ReportSavedViewShareService::class
        )->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            1
        );

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'report_saved_view_share_id' =>
                    $share->id,
                'report_saved_view_id' =>
                    $savedView->id,
                'actor_user_id' =>
                    $owner->id,
                'owner_user_id' =>
                    $owner->id,
                'recipient_user_id' =>
                    $recipient->id,
                'action' => 'shared',
                'permission_before' => null,
                'permission_after' => 'view',
            ]
        );
    }

    private function sharingFixture(
        bool $createShare = true
    ): array {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => 'Foundation Shared View',
            'filters' => [],
            'is_default' => false,
        ]);

        $share = null;

        if ($createShare) {
            $share = app(
                ReportSavedViewShareService::class
            )->share(
                $owner,
                $savedView,
                $recipient,
                'view'
            );
        }

        return [
            $owner,
            $recipient,
            $savedView,
            $share,
        ];
    }
}
