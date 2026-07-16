<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewImportApplyService;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

class ReportSavedViewPhase76CImportApplyServiceFinalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_76c_finalization_files_exist(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-76c-saved-view-import-apply-service-'
                . 'finalization.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-76c-saved-view-import-apply-service-'
                . 'finalization.md'
            )
        );
    }

    public function test_phase_76c_is_finalization_without_runtime_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 76C', $contract['phase']);
        $this->assertSame(
            'Saved View Import Apply Service Finalization',
            $contract['title']
        );
        $this->assertSame('Phase 76B clean', $contract['baseline']['phase']);
        $this->assertSame('d4f1b5c', $contract['baseline']['commit']);
        $this->assertSame(
            '1536 passed / 13917 assertions',
            $contract['baseline']['previous_tests']
        );
        $this->assertSame('finalization', $contract['scope']['type']);
        $this->assertFalse(
            $contract['scope']['implementation_changes_expected']
        );

        foreach ([
            'app/Services/ReportSavedViewImportApplyService.php',
            'app/Http/Controllers/ReportSavedViewController.php',
            'app/Support/Reports/ReportSavedViewCsvImportParser.php',
            'app/Support/Reports/'
                . 'ReportSavedViewImportExportVersionRegistry.php',
            'app/Support/Reports/ReportSavedViewRegistry.php',
            'app/Services/ReportSavedViewService.php',
            'app/Models/ReportSavedView.php',
            'app/Models/User.php',
            'routes/web.php',
            'resources/views/reports/saved-views/index.blade.php',
            'resources/views/reports/saved-views/edit.blade.php',
        ] as $lockedFile) {
            $this->assertContains(
                $lockedFile,
                $contract['scope']['locked_implementation_files']
            );
        }
    }

    public function test_final_service_identity_api_and_boundary_are_locked(): void
    {
        $reflection = new ReflectionClass(
            ReportSavedViewImportApplyService::class
        );
        $source = file_get_contents($reflection->getFileName());

        $this->assertTrue($reflection->isFinal());
        $this->assertNull($reflection->getConstructor());
        $this->assertTrue($reflection->hasMethod('apply'));
        $this->assertTrue($reflection->getMethod('apply')->isPublic());

        foreach ([
            'use App\\Models\\ReportSavedView;',
            'use App\\Models\\User;',
            'use Illuminate\\Support\\Facades\\DB;',
            'public function apply(User $user, array $rows): array',
            'return DB::transaction(function () use ($user, $rows): array',
            "(\$row['status'] ?? '') !== 'valid'",
            "->where('user_id', \$user->id)",
            "->where('report_key', \$row['report_key'])",
            "->where('name', \$row['name'])",
            "(\$row['is_default'] ?? '') === 'نعم'",
            "'filters' => \$row['filters'] ?? []",
            "'created' => \$created",
            "'skipped' => \$skipped",
        ] as $requiredMarker) {
            $this->assertStringContainsString(
                $requiredMarker,
                $source
            );
        }

        foreach ([
            'Illuminate\\Http\\',
            'Request $request',
            'response(',
            'session(',
            'auth(',
            'redirect(',
            'view(',
            'route(',
            'fopen(',
            'file_put_contents(',
            'base64_decode(',
            'ReportSavedViewCsvImportParser',
            'ReportSavedViewImportExportVersionRegistry',
        ] as $forbiddenMarker) {
            $this->assertStringNotContainsString(
                $forbiddenMarker,
                $source
            );
        }
    }

    public function test_final_service_ignores_invalid_rows_without_counts_or_writes(): void
    {
        $user = User::factory()->create();

        $result = (new ReportSavedViewImportApplyService())->apply(
            $user,
            [
                [
                    'status' => 'invalid',
                    'report_key' => 'profit-loss',
                    'name' => 'Ignored Finalization Row',
                    'is_default' => 'نعم',
                    'filters' => ['payment_status' => 'paid'],
                ],
            ]
        );

        $this->assertSame([
            'created' => 0,
            'skipped' => 0,
        ], $result);
        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_service_preserves_duplicate_scope_and_filters(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Existing Finalized Apply View',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'New Finalized Apply View',
            'filters' => ['payment_status' => 'overdue'],
            'is_default' => true,
        ]);

        $result = (new ReportSavedViewImportApplyService())->apply(
            $user,
            [
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'Existing Finalized Apply View',
                    'is_default' => 'لا',
                    'filters' => ['payment_status' => 'paid'],
                ],
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'New Finalized Apply View',
                    'is_default' => 'لا',
                    'filters' => ['payment_status' => 'partial'],
                ],
            ]
        );

        $this->assertSame([
            'created' => 1,
            'skipped' => 1,
        ], $result);

        $existing = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Existing Finalized Apply View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'unpaid'],
            $existing->filters
        );

        $created = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'New Finalized Apply View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'partial'],
            $created->filters
        );
        $this->assertFalse($created->is_default);

        $other = ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('name', 'New Finalized Apply View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'overdue'],
            $other->filters
        );
        $this->assertTrue($other->is_default);
    }

    public function test_final_service_preserves_default_scope_and_last_row_wins(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Old Finalized Profit Default',
            'filters' => [],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Old Finalized Aging Default',
            'filters' => [],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Other Finalized Profit Default',
            'filters' => [],
            'is_default' => true,
        ]);

        $result = (new ReportSavedViewImportApplyService())->apply(
            $user,
            [
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'First Finalized Profit Default',
                    'is_default' => 'نعم',
                    'filters' => [],
                ],
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'Last Finalized Profit Default',
                    'is_default' => 'نعم',
                    'filters' => [],
                ],
            ]
        );

        $this->assertSame([
            'created' => 2,
            'skipped' => 0,
        ], $result);

        $this->assertFalse(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'Old Finalized Profit Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertFalse(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'First Finalized Profit Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertTrue(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'Last Finalized Profit Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertTrue(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'Old Finalized Aging Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertTrue(
            ReportSavedView::query()
                ->where('user_id', $otherUser->id)
                ->where('name', 'Other Finalized Profit Default')
                ->firstOrFail()
                ->is_default
        );
    }

    public function test_final_controller_boundary_delegates_and_retains_http_flow(): void
    {
        $controller = file_get_contents(
            app_path('Http/Controllers/ReportSavedViewController.php')
        );

        foreach ([
            'use App\\Services\\ReportSavedViewImportApplyService;',
            'private readonly ReportSavedViewCsvImportParser $csvImportParser',
            'private readonly ReportSavedViewImportApplyService $importApplyService',
            "'csv_payload' => ['required', 'string']",
            'base64_decode((string) $validated',
            'tempnam(sys_get_temp_dir(), \'saved-view-import-\')',
            '$this->csvImportParser->parse($tempPath)',
            '$this->importApplyService->apply(',
            '$request->user(), $preview[\'rows\']',
            'تعذر قراءة ملف الاستيراد.',
            'تعذر تجهيز ملف الاستيراد.',
            'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.',
            'تم تطبيق الاستيراد: تم إنشاء ',
        ] as $marker) {
            $this->assertStringContainsString($marker, $controller);
        }

        $this->assertStringNotContainsString(
            'applySavedViewImportRows',
            $controller
        );
    }

    public function test_final_controller_blocks_invalid_file_before_service_writes(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '2',
                'Blocked Finalization View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '0',
                '',
                '{}',
                '2026-07-16 17:00:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'لم يتم تطبيق الاستيراد بسبب وجود أخطاء في الملف.'
            );

        $this->assertSame(0, ReportSavedView::query()->count());
    }

    public function test_final_controller_success_preserves_counts_defaults_and_filters(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Finalized Controller Service View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'yes',
                '1',
                'ignored summary',
                '{"payment_status":"paid"}',
                '2026-07-16 17:05:00',
            ],
        ]);

        $this->actingAs($user)
            ->post(route('reports.saved-views.import-apply'), [
                'csv_payload' => base64_encode($csv),
            ])
            ->assertRedirect(route('reports.saved-views.index'))
            ->assertSessionHas(
                'status',
                'تم تطبيق الاستيراد: تم إنشاء 1 عرض محفوظ، '
                . 'وتم تخطي 0 مكرر.'
            );

        $savedView = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Finalized Controller Service View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'paid'],
            $savedView->filters
        );
        $this->assertTrue($savedView->is_default);
    }

    public function test_phase_76c_contract_locks_behavior_and_recommends_export_writer(): void
    {
        $contract = $this->contract();

        foreach ([
            'apply_requires_authentication',
            'invalid_base64_message_preserved',
            'temp_file_failure_message_preserved',
            'invalid_file_message_preserved',
            'exact_success_message_preserved',
            'preview_remains_read_only',
            'preview_and_apply_share_parser',
            'legacy_import_preserved',
            'versioned_import_preserved',
            'filters_payload_preserved',
            'filters_summary_display_only',
            'invalid_file_blocks_all_writes',
            'duplicate_skip_without_overwrite',
            'authenticated_user_scope',
            'default_normalization_scope',
            'cross_user_records_unchanged',
            'export_behavior_preserved',
            'bulk_selection_preserved',
            'pagination_preserved',
            'phase_69_through_76_contracts_preserved',
        ] as $key) {
            $this->assertTrue(
                $contract['preserved_behavior'][$key],
                $key
            );
        }

        $this->assertSame(
            'Phase 77A',
            $contract['next_recommendation']['phase']
        );
        $this->assertSame(
            'Saved View CSV Export Writer Contract',
            $contract['next_recommendation']['title']
        );
        $this->assertFalse(
            $contract['next_recommendation']
                ['implementation_changes_expected']
        );
        $this->assertNotEmpty($contract['guardrails']);
    }

    /**
     * @return array<string, mixed>
     */
    private function contract(): array
    {
        $contract = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-76c-saved-view-import-apply-service-'
                    . 'finalization.json'
                )
            ),
            true
        );

        $this->assertIsArray($contract);

        return $contract;
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);

        return stream_get_contents($handle);
    }
}
