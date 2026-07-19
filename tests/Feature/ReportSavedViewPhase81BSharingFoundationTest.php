<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ReportSavedViewPhase81BSharingFoundationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_share_schema_model_and_relations_exist(): void
    {
        $this->assertTrue(
            Schema::hasTable(
                'report_saved_view_shares'
            )
        );

        foreach ([
            'id',
            'report_saved_view_id',
            'owner_user_id',
            'recipient_user_id',
            'permission',
            'created_at',
            'updated_at',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn(
                    'report_saved_view_shares',
                    $column
                )
            );
        }

        $share = new ReportSavedViewShare();
        $savedView = new ReportSavedView();

        $this->assertSame(
            [
                'report_saved_view_id',
                'owner_user_id',
                'recipient_user_id',
                'permission',
            ],
            $share->getFillable()
        );

        $this->assertTrue(
            method_exists($share, 'savedView')
        );
        $this->assertTrue(
            method_exists($share, 'owner')
        );
        $this->assertTrue(
            method_exists($share, 'recipient')
        );
        $this->assertTrue(
            method_exists($savedView, 'shares')
        );
        $this->assertTrue(
            method_exists(
                $savedView,
                'sharedWithUsers'
            )
        );
    }

    public function test_existing_saved_views_remain_private(): void
    {
        $owner = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Private'
        );

        $this->assertFalse(
            $savedView->shares()->exists()
        );
        $this->assertFalse(
            $savedView->sharedWithUsers()->exists()
        );
    }

    public function test_owner_can_share_and_repeat_idempotently(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $savedView = $this->savedView(
            $owner,
            'Shared'
        );

        $first = $service->share(
            $owner,
            $savedView,
            $recipient,
            ReportSavedViewShare::PERMISSION_VIEW
        );

        $second = $service->share(
            $owner,
            $savedView,
            $recipient,
            ReportSavedViewShare::PERMISSION_USE
        );

        $this->assertSame(
            $first->id,
            $second->id
        );
        $this->assertSame(
            ReportSavedViewShare::PERMISSION_USE,
            $second->permission
        );
        $this->assertSame(
            1,
            ReportSavedViewShare::query()->count()
        );
        $this->assertTrue(
            $second->canUse()
        );
    }

    public function test_self_share_and_invalid_permission_fail(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $savedView = $this->savedView(
            $owner,
            'Validation'
        );

        try {
            $service->share(
                $owner,
                $savedView,
                $owner,
                ReportSavedViewShare::PERMISSION_VIEW
            );

            $this->fail(
                'Self-sharing must fail.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'recipient_user_id',
                $exception->errors()
            );
        }

        try {
            $service->share(
                $owner,
                $savedView,
                $recipient,
                'edit'
            );

            $this->fail(
                'Invalid permission must fail.'
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'permission',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount(
            'report_saved_view_shares',
            0
        );
    }

    public function test_foreign_owner_cannot_manage_share(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $foreign = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Owned'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            ReportSavedViewShare::PERMISSION_VIEW
        );

        foreach ([
            fn () => $service->listRecipients(
                $foreign,
                $savedView
            ),
            fn () => $service->updatePermission(
                $foreign,
                $share,
                ReportSavedViewShare::PERMISSION_USE
            ),
            fn () => $service->revoke(
                $foreign,
                $share
            ),
        ] as $operation) {
            try {
                $operation();

                $this->fail(
                    'Foreign ownership operation must fail.'
                );
            } catch (NotFoundHttpException) {
                $this->assertTrue(true);
            }
        }

        $this->assertDatabaseHas(
            'report_saved_view_shares',
            [
                'id' => $share->id,
                'permission' =>
                    ReportSavedViewShare::PERMISSION_VIEW,
            ]
        );
    }

    public function test_owner_lists_recipients_and_recipient_lists_received(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $firstRecipient = User::factory()->create();
        $secondRecipient = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Recipient Lists'
        );

        $service->share(
            $owner,
            $savedView,
            $firstRecipient,
            ReportSavedViewShare::PERMISSION_VIEW
        );
        $service->share(
            $owner,
            $savedView,
            $secondRecipient,
            ReportSavedViewShare::PERMISSION_USE
        );

        $recipients = $service->listRecipients(
            $owner,
            $savedView
        );

        $this->assertCount(2, $recipients);
        $this->assertTrue(
            $recipients->every(
                fn (ReportSavedViewShare $share): bool =>
                    $share->relationLoaded('recipient')
            )
        );

        $received = $service->listReceived(
            $secondRecipient
        );

        $this->assertCount(1, $received);
        $this->assertSame(
            $savedView->id,
            $received->first()
                ->report_saved_view_id
        );
        $this->assertTrue(
            $received->first()
                ->relationLoaded('owner')
        );
        $this->assertTrue(
            $received->first()
                ->relationLoaded('savedView')
        );

        $this->assertCount(
            0,
            $service->listReceived(
                User::factory()->create()
            )
        );
    }

    public function test_update_and_revoke_are_owner_scoped(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Lifecycle'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            ReportSavedViewShare::PERMISSION_VIEW
        );

        $updated = $service->updatePermission(
            $owner,
            $share,
            ReportSavedViewShare::PERMISSION_USE
        );

        $this->assertSame(
            ReportSavedViewShare::PERMISSION_USE,
            $updated->permission
        );

        $this->assertTrue(
            $service->revoke(
                $owner,
                $updated
            )
        );

        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $share->id]
        );
    }

    public function test_database_cascades_delete_shares(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Cascade'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            ReportSavedViewShare::PERMISSION_VIEW
        );

        $savedView->delete();

        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $share->id]
        );
    }

    private function savedView(
        User $user,
        string $name
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);
    }
}
