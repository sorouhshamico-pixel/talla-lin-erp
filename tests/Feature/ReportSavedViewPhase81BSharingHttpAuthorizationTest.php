<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase81BSharingHttpAuthorizationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_sharing_routes_are_authenticated(): void
    {
        foreach ([
            'reports.saved-views.shares.store' =>
                'POST',
            'reports.saved-view-shares.update' =>
                'PATCH',
            'reports.saved-view-shares.destroy' =>
                'DELETE',
            'reports.shared-saved-views.index' =>
                'GET',
            'reports.shared-saved-views.apply' =>
                'GET',
        ] as $routeName => $method) {
            $route = Route::getRoutes()
                ->getByName($routeName);

            $this->assertNotNull(
                $route,
                $routeName
            );
            $this->assertContains(
                $method,
                $route->methods()
            );
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_owner_can_create_update_and_revoke_share(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $savedView = $this->savedView(
            $owner,
            'HTTP Owner'
        );

        $this->actingAs($owner)
            ->from('/reports')
            ->post(
                route(
                    'reports.saved-views.shares.store',
                    $savedView
                ),
                [
                    'recipient_user_id' =>
                        $recipient->id,
                    'permission' => 'view',
                ]
            )
            ->assertRedirect('/reports');

        $share = ReportSavedViewShare::query()
            ->firstOrFail();

        $this->actingAs($owner)
            ->from('/reports')
            ->patch(
                route(
                    'reports.saved-view-shares.update',
                    $share
                ),
                ['permission' => 'use']
            )
            ->assertRedirect('/reports');

        $this->assertSame(
            'use',
            $share->fresh()->permission
        );

        $this->actingAs($owner)
            ->from('/reports')
            ->delete(
                route(
                    'reports.saved-view-shares.destroy',
                    $share
                )
            )
            ->assertRedirect('/reports');

        $this->assertDatabaseMissing(
            'report_saved_view_shares',
            ['id' => $share->id]
        );
    }

    public function test_foreign_user_cannot_manage_owner_share(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $foreign = User::factory()->create();
        $savedView = $this->savedView(
            $owner,
            'Foreign Guard'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $this->actingAs($foreign)
            ->post(
                route(
                    'reports.saved-views.shares.store',
                    $savedView
                ),
                [
                    'recipient_user_id' =>
                        $foreign->id,
                    'permission' => 'view',
                ]
            )
            ->assertNotFound();

        $this->actingAs($foreign)
            ->patch(
                route(
                    'reports.saved-view-shares.update',
                    $share
                ),
                ['permission' => 'use']
            )
            ->assertNotFound();

        $this->actingAs($foreign)
            ->delete(
                route(
                    'reports.saved-view-shares.destroy',
                    $share
                )
            )
            ->assertNotFound();
    }

    public function test_recipient_index_is_scoped(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $otherRecipient = User::factory()->create();

        $first = $this->savedView(
            $owner,
            'Visible Share'
        );
        $second = $this->savedView(
            $owner,
            'Hidden Share'
        );

        $visibleShare = $service->share(
            $owner,
            $first,
            $recipient,
            'view'
        );
        $service->share(
            $owner,
            $second,
            $otherRecipient,
            'use'
        );

        $this->actingAs($recipient)
            ->getJson(
                route(
                    'reports.shared-saved-views.index'
                )
            )
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $visibleShare->id
            )
            ->assertJsonPath(
                'data.0.saved_view.name',
                'Visible Share'
            );
    }

    public function test_view_permission_cannot_apply(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $savedView = $this->savedView(
            $owner,
            'View Only'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertNotFound();
    }

    public function test_use_permission_can_apply_active_source(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Usable',
            [
                'from_date' => '2026-07-01',
                'to_date' => '2026-07-31',
            ]
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $response = $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertRedirect();

        $location = $response->headers->get(
            'Location'
        );

        $this->assertStringContainsString(
            '/reports/profit-loss',
            $location
        );
        $this->assertStringContainsString(
            'from_date=2026-07-01',
            $location
        );
    }

    public function test_archived_or_foreign_share_cannot_apply(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $foreign = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Archived'
        );

        $share = $service->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $savedView->forceFill([
            'archived_at' => now(),
        ])->save();

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertNotFound();

        $savedView->forceFill([
            'archived_at' => null,
        ])->save();

        $this->actingAs($foreign)
            ->get(
                route(
                    'reports.shared-saved-views.apply',
                    $share
                )
            )
            ->assertNotFound();
    }

    private function savedView(
        User $owner,
        string $name,
        array $filters = []
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => $filters,
            'is_default' => false,
        ]);
    }
}
