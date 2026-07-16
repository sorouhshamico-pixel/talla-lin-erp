<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewService;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportSavedViewPhase78BSelectedCsvExportImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_78b_files_and_documentation_exist(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-78b-selected-saved-view-csv-export-'
                . 'implementation.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-78b-selected-saved-view-csv-export-'
                . 'implementation.md'
            )
        );

        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-78b-selected-saved-view-csv-export-'
                    . 'implementation.json'
                )
            ),
            true
        );

        $this->assertSame('Phase 78B', $document['phase']);
        $this->assertSame('implementation', $document['type']);
        $this->assertSame('Phase 78A', $document['baseline']['phase']);
        $this->assertSame('18860e1', $document['baseline']['commit']);
        $this->assertTrue(
            $document['implementation']
                ['authenticated_user_scope_enforced']
        );
        $this->assertFalse(
            $document['implementation']['writer_runtime_changes']
        );
        $this->assertSame(
            'Phase 78C',
            $document['next_recommendation']['phase']
        );
    }

    public function test_selected_export_route_is_authenticated_and_post_only(): void
    {
        $route = Route::getRoutes()->getByName(
            'reports.saved-views.export-selected'
        );

        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('GET', $route->methods());
        $this->assertSame(
            'reports/saved-views/export-selected',
            $route->uri()
        );
        $this->assertContains('auth', $route->gatherMiddleware());

        $this->post(route('reports.saved-views.export-selected'), [
            'saved_view_ids' => [1],
        ])->assertRedirect(route('login'));
    }

    public function test_selected_export_request_validation_rejects_invalid_ids(): void
    {
        $user = User::factory()->create();
        $route = route('reports.saved-views.export-selected');

        $this->actingAs($user)
            ->post($route, [])
            ->assertSessionHasErrors('saved_view_ids');

        $this->actingAs($user)
            ->post($route, [
                'saved_view_ids' => [],
            ])
            ->assertSessionHasErrors('saved_view_ids');

        $this->actingAs($user)
            ->post($route, [
                'saved_view_ids' => '1',
            ])
            ->assertSessionHasErrors('saved_view_ids');

        $this->actingAs($user)
            ->post($route, [
                'saved_view_ids' => [1, 1],
            ])
            ->assertSessionHasErrors('saved_view_ids.1');

        $this->actingAs($user)
            ->post($route, [
                'saved_view_ids' => ['not-an-integer'],
            ])
            ->assertSessionHasErrors('saved_view_ids.0');
    }

    public function test_service_normalizes_ids_scopes_user_and_preserves_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $sameFirst = $this->createSavedView(
            $user,
            'profit-loss',
            'Same Name',
            false
        );
        $sameSecond = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Same Name',
            false
        );
        $alpha = $this->createSavedView(
            $user,
            'profit-loss',
            'Alpha',
            false
        );
        $defaultZulu = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Zulu Default',
            true
        );
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign',
            true
        );

        $result = app(ReportSavedViewService::class)
            ->exportSelectedForManagement($user, [
                $sameSecond->id,
                $foreign->id,
                0,
                -1,
                999999,
                $defaultZulu->id,
                $alpha->id,
                $sameFirst->id,
                $sameFirst->id,
            ]);

        $this->assertSame([
            $defaultZulu->id,
            $alpha->id,
            $sameFirst->id,
            $sameSecond->id,
        ], $result->pluck('id')->all());
        $this->assertFalse($result->contains('id', $foreign->id));
    }

    public function test_selected_export_outputs_owned_rows_exactly_and_uses_selected_filename(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $alpha = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Alpha Selected',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_61_90',
            ],
            'is_default' => false,
        ]);
        $defaultZulu = ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Zulu Selected',
            'filters' => [
                'payment_status' => 'paid',
            ],
            'is_default' => true,
        ]);
        $unselected = $this->createSavedView(
            $user,
            'profit-loss',
            'Unselected',
            false
        );
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign Selected',
            true
        );

        $this->travelTo(
            Carbon::create(2026, 7, 16, 12, 34, 56)
        );

        $response = $this->actingAs($user)
            ->post(route('reports.saved-views.export-selected'), [
                'saved_view_ids' => [
                    $alpha->id,
                    $foreign->id,
                    $defaultZulu->id,
                    999999,
                ],
            ])
            ->assertOk()
            ->assertHeader(
                'content-type',
                'text/csv; charset=UTF-8'
            )
            ->assertDownload(
                'saved-views-selected-20260716-123456.csv'
            );

        $csv = $response->streamedContent();
        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
        $this->assertSame([
            'Zulu Selected',
            'Alpha Selected',
        ], array_column(array_slice($rows, 1), 1));
        $this->assertStringNotContainsString(
            $unselected->name,
            $csv
        );
        $this->assertStringNotContainsString(
            $foreign->name,
            $csv
        );
        $this->assertSame([
            'payment_status' => 'paid',
        ], json_decode($rows[1][7], true));
        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_61_90',
        ], json_decode($rows[2][7], true));
    }

    public function test_selected_export_returns_header_only_when_no_owned_ids_match(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign Only',
            true
        );

        $response = $this->actingAs($user)
            ->post(route('reports.saved-views.export-selected'), [
                'saved_view_ids' => [
                    $foreign->id,
                    999999,
                ],
            ])
            ->assertOk();

        $csv = $response->streamedContent();
        $rows = $this->parseCsv($csv);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertCount(1, $rows);
        $this->assertSame(
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            $rows[0]
        );
    }

    public function test_selected_export_round_trips_through_existing_import(): void
    {
        $sourceUser = User::factory()->create();
        $targetUser = User::factory()->create();

        $selected = ReportSavedView::query()->create([
            'user_id' => $sourceUser->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Selected Round Trip',
            'filters' => [
                'payment_status' => 'partial',
                'aging_bucket' => 'overdue_31_60',
            ],
            'is_default' => true,
        ]);
        $this->createSavedView(
            $sourceUser,
            'profit-loss',
            'Not Exported',
            false
        );

        $csv = $this->actingAs($sourceUser)
            ->post(route('reports.saved-views.export-selected'), [
                'saved_view_ids' => [$selected->id],
            ])
            ->assertOk()
            ->streamedContent();

        $this->actingAs($targetUser)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، '
                . 'وتم تخطي 0 مكرر.'
            );

        $imported = ReportSavedView::query()
            ->where('user_id', $targetUser->id)
            ->where('name', 'Selected Round Trip')
            ->firstOrFail();

        $this->assertSame([
            'payment_status' => 'partial',
            'aging_bucket' => 'overdue_31_60',
        ], $imported->filters);
        $this->assertTrue($imported->is_default);
        $this->assertDatabaseMissing('report_saved_views', [
            'user_id' => $targetUser->id,
            'name' => 'Not Exported',
        ]);
    }

    public function test_existing_filtered_export_remains_unchanged(): void
    {
        $user = User::factory()->create();

        $included = $this->createSavedView(
            $user,
            'profit-loss',
            'Filtered Included',
            false
        );
        $excluded = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Filtered Excluded',
            false
        );

        $csv = $this->actingAs($user)
            ->get(route('reports.saved-views.export', [
                'report_key' => 'profit-loss',
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString($included->name, $csv);
        $this->assertStringNotContainsString($excluded->name, $csv);
    }

    public function test_existing_bulk_delete_works_with_button_scoped_method_override(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $selected = $this->createSavedView(
            $user,
            'profit-loss',
            'Delete Selected',
            false
        );
        $kept = $this->createSavedView(
            $user,
            'sales-invoice-aging',
            'Keep Selected',
            false
        );
        $foreign = $this->createSavedView(
            $otherUser,
            'profit-loss',
            'Foreign Delete Attempt',
            false
        );

        $this->actingAs($user)
            ->post(route('reports.saved-views.bulk-destroy'), [
                '_method' => 'DELETE',
                'saved_view_ids' => [
                    $selected->id,
                    $foreign->id,
                ],
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم حذف 1 من العروض المحددة.'
            );

        $this->assertDatabaseMissing('report_saved_views', [
            'id' => $selected->id,
        ]);
        $this->assertDatabaseHas('report_saved_views', [
            'id' => $kept->id,
        ]);
        $this->assertDatabaseHas('report_saved_views', [
            'id' => $foreign->id,
        ]);
    }

    public function test_management_view_reuses_selection_for_export_and_delete(): void
    {
        $user = User::factory()->create();
        $this->createSavedView(
            $user,
            'profit-loss',
            'View Selection',
            false
        );

        $this->actingAs($user)
            ->get(route('reports.saved-views.index'))
            ->assertOk()
            ->assertSee(
                'data-testid="report-saved-views-export-selected-button"',
                false
            )
            ->assertSee('تصدير المحدد CSV')
            ->assertSee(
                'data-testid="report-saved-views-bulk-delete-button"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-view-bulk-select-checkbox"',
                false
            )
            ->assertSee(
                'data-testid="report-saved-views-select-all-checkbox"',
                false
            );

        $view = file_get_contents(
            resource_path('views/reports/saved-views/index.blade.php')
        );

        foreach ([
            "action=\"{{ route('reports.saved-views.export-selected') }}\"",
            "formaction=\"{{ route('reports.saved-views.bulk-destroy') }}\"",
            'name="_method"',
            'value="DELETE"',
            'const bulkExportButton =',
            'bulkExportButton.disabled = selectedCount === 0',
            'submitter === bulkDeleteButton',
        ] as $marker) {
            $this->assertStringContainsString($marker, $view);
        }
    }

    public function test_phase_78b_source_contains_exact_boundaries(): void
    {
        $routes = file_get_contents(base_path('routes/web.php'));
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );
        $service = file_get_contents(
            app_path('Services/ReportSavedViewService.php')
        );
        $writer = file_get_contents(
            app_path(
                'Support/Reports/ReportSavedViewCsvExportWriter.php'
            )
        );

        foreach ([
            "Route::post('/reports/saved-views/export-selected'",
            "'exportSelected'",
            "'reports.saved-views.export-selected'",
        ] as $marker) {
            $this->assertStringContainsString($marker, $routes);
        }

        foreach ([
            'public function exportSelected(',
            "'saved_view_ids' => ['required', 'array', 'min:1']",
            "'saved_view_ids.*' => ['integer', 'distinct']",
            '$savedViewService->exportSelectedForManagement(',
            "'saved-views-selected-'",
            '$this->csvExportWriter->write($formattedSavedViews)',
            "'Content-Type' => 'text/csv; charset=UTF-8'",
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        foreach ([
            'public function exportSelectedForManagement(',
            "->where('user_id', \$user->id)",
            "->whereIn('id', \$selectedIds)",
            "->orderByDesc('is_default')",
            "->orderBy('name')",
            "->orderBy('id')",
        ] as $marker) {
            $this->assertStringContainsString($marker, $service);
        }

        $this->assertStringNotContainsString(
            'exportSelectedForManagement',
            $writer
        );
        $this->assertStringNotContainsString(
            'Request $request',
            $writer
        );
    }

    private function createSavedView(
        User $user,
        string $reportKey,
        string $name,
        bool $isDefault
    ): ReportSavedView {
        return ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => $reportKey,
            'name' => $name,
            'filters' => [],
            'is_default' => $isDefault,
        ]);
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseCsv(string $csv): array
    {
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, substr($csv, 3));
        rewind($handle);

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }
}
