<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase81BSharingManagementUiTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_owner_management_page_is_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $foreign = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'Owner Managed'
        );

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
                    'reports.saved-views.shares.index',
                    $savedView
                )
            )
            ->assertOk()
            ->assertSee(
                'data-testid="report-saved-view-share-manager"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-share-recipient"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-share-permission"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-share-revoke-button"',
                false
            )
            ->assertSee($recipient->name);

        $this->actingAs($foreign)
            ->get(
                route(
                    'reports.saved-views.shares.index',
                    $savedView
                )
            )
            ->assertNotFound();
    }

    public function test_owner_page_excludes_owner_from_recipient_options(): void
    {
        $owner = User::factory()->create([
            'name' => 'Current Owner',
        ]);

        $recipient = User::factory()->create([
            'name' => 'Available Recipient',
        ]);

        $savedView = $this->savedView(
            $owner,
            'Recipient Options'
        );

        $this->actingAs($owner)
            ->get(
                route(
                    'reports.saved-views.shares.index',
                    $savedView
                )
            )
            ->assertOk()
            ->assertDontSee(
                '<option value="'.$owner->id.'">',
                false
            )
            ->assertSee(
                '<option value="'.$recipient->id.'">',
                false
            );
    }

    public function test_recipient_html_page_shows_received_only(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();
        $other = User::factory()->create();

        $visible = $this->savedView(
            $owner,
            'Visible UI Share'
        );

        $hidden = $this->savedView(
            $owner,
            'Other User Share'
        );

        $service->share(
            $owner,
            $visible,
            $recipient,
            'use'
        );

        $service->share(
            $owner,
            $hidden,
            $other,
            'use'
        );

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-testid="shared-saved-views-page"',
                false
            )
            ->assertSee(
                'data-testid="shared-saved-view-row"',
                false
            )
            ->assertSee('Visible UI Share')
            ->assertDontSee('Other User Share')
            ->assertSee(
                'data-testid="shared-saved-view-apply-button"',
                false
            )
            ->assertSee(
                'data-testid="shared-saved-view-copy-button"',
                false
            );
    }

    public function test_view_only_share_hides_apply_action(): void
    {
        $service = app(
            ReportSavedViewShareService::class
        );

        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = $this->savedView(
            $owner,
            'View Only UI'
        );

        $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.index'
                )
            )
            ->assertOk()
            ->assertSee('View Only UI')
            ->assertDontSee(
                'data-testid="shared-saved-view-apply-button"',
                false
            )
            ->assertSee('التطبيق غير متاح');
    }

    public function test_empty_recipient_page_has_empty_state(): void
    {
        $recipient = User::factory()->create();

        $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-views.index'
                )
            )
            ->assertOk()
            ->assertSee(
                'data-testid="shared-saved-views-empty"',
                false
            );
    }

    private function savedView(
        User $owner,
        string $name
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);
    }
}
