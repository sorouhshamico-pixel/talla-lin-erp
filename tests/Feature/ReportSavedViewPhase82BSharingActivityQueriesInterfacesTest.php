<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityService;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase82BSharingActivityQueriesInterfacesTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_routes_are_authenticated(): void
    {
        foreach ([
            'reports.saved-view-share-activities.owner.index',
            'reports.shared-saved-view-activities.index',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_owner_and_recipient_queries_are_scoped(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Visible Activity');

        $otherRecipient = User::factory()->create();

        $shareService = app(
            ReportSavedViewShareService::class
        );

        $shareService->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );
        $shareService->share(
            $owner,
            $savedView,
            $otherRecipient,
            'use'
        );

        $activityService = app(
            ReportSavedViewShareActivityService::class
        );

        $ownerPage =
            $activityService->paginateForOwner($owner);
        $recipientPage =
            $activityService->paginateForRecipient(
                $recipient
            );

        $this->assertSame(2, $ownerPage->total());
        $this->assertSame(1, $recipientPage->total());
        $this->assertSame(
            $recipient->id,
            $recipientPage->items()[0]
                ->recipient_user_id
        );
    }

    public function test_action_and_recipient_filters_work(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Filtered Activity');

        $otherRecipient = User::factory()->create();

        $shareService = app(
            ReportSavedViewShareService::class
        );

        $shareService->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );
        $shareService->share(
            $owner,
            $savedView,
            $otherRecipient,
            'use'
        );

        $page = app(
            ReportSavedViewShareActivityService::class
        )->paginateForOwner(
            $owner,
            'shared',
            $recipient->id
        );

        $this->assertSame(1, $page->total());
        $this->assertSame(
            $recipient->id,
            $page->items()[0]->recipient_user_id
        );
    }

    public function test_html_and_json_interfaces_render(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Interface Activity');

        app(ReportSavedViewShareService::class)
            ->share(
                $owner,
                $savedView,
                $recipient,
                'view'
            );

        $this->actingAs($owner)
            ->get(
                route(
                    'reports.saved-view-share-activities.owner.index'
                )
            )
            ->assertOk()
            ->assertSee('Interface Activity')
            ->assertSee(
                'saved-view-share-owner-activities-page',
                false
            );

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-view-activities.index'
                )
            )
            ->assertOk()
            ->assertSee('Interface Activity')
            ->assertSee(
                'shared-saved-view-recipient-activities-page',
                false
            );

        $this->actingAs($owner)
            ->getJson(
                route(
                    'reports.saved-view-share-activities.owner.index'
                )
            )
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.action', 'shared');
    }

    public function test_empty_states_render(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(
                route(
                    'reports.saved-view-share-activities.owner.index'
                )
            )
            ->assertOk()
            ->assertSee('owner-activity-empty', false);

        $this->actingAs($user)
            ->get(
                route(
                    'reports.shared-saved-view-activities.index'
                )
            )
            ->assertOk()
            ->assertSee('recipient-activity-empty', false);
    }

    private function fixture(string $name): array
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);

        return [$owner, $recipient, $savedView];
    }
}
