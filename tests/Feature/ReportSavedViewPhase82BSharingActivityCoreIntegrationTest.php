<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReportSavedViewPhase82BSharingActivityCoreIntegrationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_new_share_records_shared_activity(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $share = $this->service()->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $activity = ReportSavedViewShareActivity::query()
            ->sole();

        $this->assertSame(
            'shared',
            $activity->action
        );
        $this->assertSame(
            $share->id,
            $activity->report_saved_view_share_id
        );
        $this->assertSame(
            $owner->id,
            $activity->actor_user_id
        );
        $this->assertSame(
            $recipient->id,
            $activity->recipient_user_id
        );
        $this->assertNull(
            $activity->permission_before
        );
        $this->assertSame(
            'view',
            $activity->permission_after
        );
    }

    public function test_identical_repeat_share_is_idempotent(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $service = $this->service();

        $first = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $second = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $this->assertSame(
            $first->id,
            $second->id
        );
        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            1
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            ['action' => 'shared']
        );
    }

    public function test_repeat_share_with_change_records_permission_updated(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $service = $this->service();

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $updated = $service->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $this->assertSame(
            $share->id,
            $updated->id
        );
        $this->assertSame(
            'use',
            $updated->permission
        );

        $activity = ReportSavedViewShareActivity::query()
            ->where(
                'action',
                'permission_updated'
            )
            ->sole();

        $this->assertSame(
            'view',
            $activity->permission_before
        );
        $this->assertSame(
            'use',
            $activity->permission_after
        );
    }

    public function test_explicit_permission_update_records_only_real_change(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $service = $this->service();

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $service->updatePermission(
            $owner,
            $share,
            'view'
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            1
        );

        $service->updatePermission(
            $owner,
            $share,
            'use'
        );

        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            2
        );
        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'action' => 'permission_updated',
                'permission_before' => 'view',
                'permission_after' => 'use',
            ]
        );
    }

    public function test_revoke_records_activity_that_survives_share_delete(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $service = $this->service();

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $shareId = $share->id;

        $this->assertTrue(
            $service->revoke(
                $owner,
                $share
            )
        );

        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $shareId]
        );

        $activity = ReportSavedViewShareActivity::query()
            ->where('action', 'revoked')
            ->sole();

        $this->assertNull(
            $activity->report_saved_view_share_id
        );
        $this->assertSame(
            $savedView->id,
            $activity->report_saved_view_id
        );
        $this->assertSame(
            'use',
            $activity->permission_before
        );
        $this->assertNull(
            $activity->permission_after
        );
    }

    public function test_successful_apply_records_activity(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $share = $this->service()->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertRedirect();

        $this->assertDatabaseHas(
            'report_saved_view_share_activities',
            [
                'report_saved_view_share_id' =>
                    $share->id,
                'actor_user_id' =>
                    $recipient->id,
                'action' => 'applied',
                'permission_before' => 'use',
                'permission_after' => 'use',
            ]
        );
    }

    public function test_failed_apply_records_no_activity(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $share = $this->service()->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $before = ReportSavedViewShareActivity::query()
            ->count();

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertNotFound();

        $this->assertSame(
            $before,
            ReportSavedViewShareActivity::query()
                ->count()
        );
    }

    public function test_copy_records_activity_with_copy_identifier(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $service = $this->service();

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $copy = $service->copyToRecipient(
            $recipient,
            $share
        );

        $activity = ReportSavedViewShareActivity::query()
            ->where('action', 'copied')
            ->sole();

        $this->assertSame(
            $recipient->id,
            $activity->actor_user_id
        );
        $this->assertSame(
            $copy->id,
            $activity->metadata[
                'copied_saved_view_id'
            ]
        );
        $this->assertSame(
            $savedView->id,
            $activity->report_saved_view_id
        );
    }

    public function test_invalid_or_unauthorized_operations_record_nothing(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture();

        $foreign = User::factory()->create();

        try {
            $this->service()->share(
                $owner,
                $savedView,
                $recipient,
                'manage'
            );

            $this->fail(
                'Invalid permission must fail.'
            );
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->assertDatabaseCount(
            'report_saved_view_share_activities',
            0
        );

        $share = $this->service()->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $before = ReportSavedViewShareActivity::query()
            ->count();

        $this->actingAs($foreign)
            ->patch(
                route(
                    'reports.saved-view-shares.update',
                    $share
                ),
                ['permission' => 'use']
            )
            ->assertNotFound();

        $this->assertSame(
            $before,
            ReportSavedViewShareActivity::query()
                ->count()
        );
    }

    private function service(): ReportSavedViewShareService
    {
        return app(
            ReportSavedViewShareService::class
        );
    }

    private function fixture(): array
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => 'Core Activity View',
            'filters' => [],
            'is_default' => false,
        ]);

        return [
            $owner,
            $recipient,
            $savedView,
        ];
    }
}
