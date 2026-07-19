<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class ReportSavedViewPhase83BSharingActivityExportImplementationTest
    extends TestCase
{
    use RefreshDatabase;

    public function test_export_routes_are_authenticated(): void
    {
        foreach ([
            'reports.saved-view-share-activities.owner.export',
            'reports.shared-saved-view-activities.export',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains(
                'GET',
                $route->methods()
            );
            $this->assertContains(
                'auth',
                $route->gatherMiddleware()
            );
        }
    }

    public function test_owner_export_is_scoped_and_has_locked_columns(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Owner Exported View');

        [$foreignOwner, $foreignRecipient, $foreignView] =
            $this->fixture('Foreign Hidden View');

        $service = app(
            ReportSavedViewShareService::class
        );

        $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );

        $service->share(
            $foreignOwner,
            $foreignView,
            $foreignRecipient,
            'use'
        );

        $response = $this->actingAs($owner)
            ->get(
                route(
                    'reports.saved-view-share-activities.owner.export'
                )
            );

        $this->assertSame(
            200,
            $response->baseResponse->getStatusCode()
        );
        $this->assertInstanceOf(
            StreamedResponse::class,
            $response->baseResponse
        );

        $csv = $this->streamedContent(
            $response->baseResponse
        );

        $this->assertStringStartsWith(
            "\xEF\xBB\xBF",
            $csv
        );
        $this->assertStringContainsString(
            'activity_id,created_at,action,source_saved_view_id,source_name,source_report_key,actor_user_id,actor_name,owner_user_id,owner_name,recipient_user_id,recipient_name,permission_before,permission_after,copied_saved_view_id',
            $csv
        );
        $this->assertStringContainsString(
            'Owner Exported View',
            $csv
        );
        $this->assertStringNotContainsString(
            'Foreign Hidden View',
            $csv
        );
    }

    public function test_recipient_export_is_strictly_scoped(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Recipient Visible View');

        $otherRecipient = User::factory()->create();

        $service = app(
            ReportSavedViewShareService::class
        );

        $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );
        $service->share(
            $owner,
            $savedView,
            $otherRecipient,
            'use'
        );

        $response = $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-view-activities.export'
                )
            );

        $csv = $this->streamedContent(
            $response->baseResponse
        );

        $rows = $this->csvRows($csv);

        $this->assertCount(2, $rows);
        $this->assertSame(
            (string) $recipient->id,
            $rows[1][10]
        );
    }

    public function test_owner_filters_match_history_semantics(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Filtered Export View');

        $otherRecipient = User::factory()->create();

        $service = app(
            ReportSavedViewShareService::class
        );

        $service->share(
            $owner,
            $savedView,
            $recipient,
            'view'
        );
        $service->share(
            $owner,
            $savedView,
            $otherRecipient,
            'use'
        );

        $response = $this->actingAs($owner)
            ->get(
                route(
                    'reports.saved-view-share-activities.owner.export',
                    [
                        'action' => 'shared',
                        'recipient_user_id' =>
                            $recipient->id,
                        'report_saved_view_id' =>
                            $savedView->id,
                    ]
                )
            );

        $rows = $this->csvRows(
            $this->streamedContent(
                $response->baseResponse
            )
        );

        $this->assertCount(2, $rows);
        $this->assertSame(
            (string) $recipient->id,
            $rows[1][10]
        );
    }

    public function test_copy_export_extracts_only_copy_identifier(): void
    {
        [$owner, $recipient, $savedView] =
            $this->fixture('Copied Export View');

        $shareService = app(
            ReportSavedViewShareService::class
        );

        $share = $shareService->share(
            $owner,
            $savedView,
            $recipient,
            'use'
        );

        $copy = $shareService->copyToRecipient(
            $recipient,
            $share
        );

        $response = $this->actingAs($recipient)
            ->get(
                route(
                    'reports.shared-saved-view-activities.export',
                    ['action' => 'copied']
                )
            );

        $csv = $this->streamedContent(
            $response->baseResponse
        );
        $rows = $this->csvRows($csv);

        $this->assertCount(2, $rows);
        $this->assertSame(
            (string) $copy->id,
            $rows[1][14]
        );
        $this->assertStringNotContainsString(
            'metadata',
            $csv
        );
        $this->assertStringNotContainsString(
            'filters_payload',
            $csv
        );
    }

    public function test_empty_authorized_export_is_valid(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(
                route(
                    'reports.saved-view-share-activities.owner.export'
                )
            );

        $this->assertSame(
            200,
            $response->baseResponse->getStatusCode()
        );

        $rows = $this->csvRows(
            $this->streamedContent(
                $response->baseResponse
            )
        );

        $this->assertCount(1, $rows);
    }

    public function test_download_headers_and_filename_are_locked(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(
                route(
                    'reports.shared-saved-view-activities.export'
                )
            );

        $this->assertSame(
            'text/csv; charset=UTF-8',
            $response->baseResponse
                ->headers
                ->get('content-type')
        );

        $disposition = (string) $response
            ->baseResponse
            ->headers
            ->get('content-disposition');

        $this->assertStringContainsString(
            'saved-view-sharing-activity-recipient-',
            $disposition
        );
        $this->assertStringContainsString(
            '.csv',
            $disposition
        );
    }

    private function fixture(
        string $name
    ): array {
        $owner = User::factory()->create();
        $recipient = User::factory()->create();

        $savedView = ReportSavedView::query()->create([
            'user_id' => $owner->id,
            'report_key' => 'profit-loss',
            'name' => $name,
            'filters' => [],
            'is_default' => false,
        ]);

        return [
            $owner,
            $recipient,
            $savedView,
        ];
    }

    private function streamedContent(
        StreamedResponse $response
    ): string {
        ob_start();

        $response->sendContent();

        $content = ob_get_clean();

        $this->assertIsString($content);

        return $content;
    }

    private function csvRows(
        string $content
    ): array {
        $content = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $content
        );

        $handle = fopen(
            'php://temp',
            'r+'
        );

        $this->assertNotFalse($handle);

        fwrite($handle, $content);
        rewind($handle);

        $rows = [];

        while (
            ($row = fgetcsv(
                $handle,
                0,
                ',',
                '"',
                '\\'
            )) !== false
        ) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
