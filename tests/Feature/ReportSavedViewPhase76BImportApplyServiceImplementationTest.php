<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use App\Services\ReportSavedViewImportApplyService;
use App\Support\Reports\ReportSavedViewImportExportVersionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;
use Throwable;

class ReportSavedViewPhase76BImportApplyServiceImplementationTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_76b_implementation_files_exist(): void
    {
        $this->assertFileExists(
            app_path('Services/ReportSavedViewImportApplyService.php')
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-76b-saved-view-import-apply-service-'
                . 'implementation.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-76b-saved-view-import-apply-service-'
                . 'implementation.md'
            )
        );
    }

    public function test_service_is_final_stateless_transactional_and_http_free(): void
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

    public function test_service_ignores_invalid_rows_without_counting_them(): void
    {
        $user = User::factory()->create();

        $result = (new ReportSavedViewImportApplyService())->apply(
            $user,
            [
                [
                    'status' => 'invalid',
                    'report_key' => 'profit-loss',
                    'name' => 'Ignored Invalid Row',
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

    public function test_service_preserves_duplicate_scope_filters_and_counts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Existing Service View',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => false,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Imported Service View',
            'filters' => ['payment_status' => 'overdue'],
            'is_default' => true,
        ]);

        $result = (new ReportSavedViewImportApplyService())->apply(
            $user,
            [
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'Existing Service View',
                    'is_default' => 'لا',
                    'filters' => ['payment_status' => 'paid'],
                ],
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'Imported Service View',
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
            ->where('name', 'Existing Service View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'unpaid'],
            $existing->filters
        );

        $imported = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Imported Service View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'partial'],
            $imported->filters
        );
        $this->assertFalse($imported->is_default);

        $other = ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('name', 'Imported Service View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'overdue'],
            $other->filters
        );
        $this->assertTrue($other->is_default);
    }

    public function test_service_preserves_default_normalization_scope_and_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Old Profit Default',
            'filters' => [],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'sales-invoice-aging',
            'name' => 'Aging Default',
            'filters' => [],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Other Profit Default',
            'filters' => [],
            'is_default' => true,
        ]);

        $result = (new ReportSavedViewImportApplyService())->apply(
            $user,
            [
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'First New Profit Default',
                    'is_default' => 'نعم',
                    'filters' => [],
                ],
                [
                    'status' => 'valid',
                    'report_key' => 'profit-loss',
                    'name' => 'Second New Profit Default',
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
                ->where('name', 'Old Profit Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertFalse(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'First New Profit Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertTrue(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'Second New Profit Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertTrue(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'Aging Default')
                ->firstOrFail()
                ->is_default
        );
        $this->assertTrue(
            ReportSavedView::query()
                ->where('user_id', $otherUser->id)
                ->where('name', 'Other Profit Default')
                ->firstOrFail()
                ->is_default
        );
    }

    public function test_service_rolls_back_the_entire_batch_on_write_failure(): void
    {
        $user = User::factory()->create();

        try {
            (new ReportSavedViewImportApplyService())->apply(
                $user,
                [
                    [
                        'status' => 'valid',
                        'report_key' => 'profit-loss',
                        'name' => 'Must Roll Back',
                        'is_default' => 'لا',
                        'filters' => [],
                    ],
                    [
                        'status' => 'valid',
                        'report_key' => null,
                        'name' => 'Invalid Database Row',
                        'is_default' => 'لا',
                        'filters' => [],
                    ],
                ]
            );

            $this->fail(
                'Expected the second write to fail and roll back.'
            );
        } catch (Throwable $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }

        $this->assertFalse(
            ReportSavedView::query()
                ->where('user_id', $user->id)
                ->where('name', 'Must Roll Back')
                ->exists()
        );
    }

    public function test_controller_injects_service_and_retains_http_parser_and_messages(): void
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

    public function test_controller_apply_flow_preserves_exact_result_and_filters(): void
    {
        $user = User::factory()->create();

        $csv = $this->csv([
            ReportSavedViewImportExportVersionRegistry::exportHeader(),
            [
                '1',
                'Controller Service Flow',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'yes',
                '1',
                'ignored summary',
                '{"payment_status":"paid"}',
                '2026-07-16 16:00:00',
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
            ->where('name', 'Controller Service Flow')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'paid'],
            $savedView->filters
        );
        $this->assertTrue($savedView->is_default);
    }

    public function test_phase_76b_documentation_records_behavior_preserving_extraction(): void
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-76b-saved-view-import-apply-service-'
                    . 'implementation.json'
                )
            ),
            true
        );

        $this->assertSame('Phase 76B', $document['phase']);
        $this->assertSame('Phase 76A clean', $document['baseline']['phase']);
        $this->assertSame('6d0e2b1', $document['baseline']['commit']);
        $this->assertSame(
            '1527 passed / 13835 assertions',
            $document['baseline']['previous_tests']
        );

        foreach ([
            'service_created',
            'service_is_final',
            'service_is_stateless',
            'transaction_extracted',
            'valid_row_guard_extracted',
            'duplicate_detection_extracted',
            'default_normalization_extracted',
            'record_creation_extracted',
            'result_counting_extracted',
            'controller_injects_service',
            'http_and_parser_flow_remain_in_controller',
            'runtime_behavior_preserved',
        ] as $key) {
            $this->assertTrue(
                $document['implementation'][$key],
                $key
            );
        }

        $this->assertSame(
            'Phase 76C',
            $document['next_recommendation']['phase']
        );
        $this->assertSame(
            'Finalize Saved View Import Apply Service',
            $document['next_recommendation']['title']
        );
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
