<?php

namespace Tests\Feature;

use App\Models\ReportSavedView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportSavedViewPhase76AImportApplyServiceContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_76a_contract_files_exist(): void
    {
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-76a-saved-view-import-apply-service-contract.json'
            )
        );
        $this->assertFileExists(
            base_path(
                'docs/'
                . 'phase-76a-saved-view-import-apply-service-contract.md'
            )
        );
    }

    public function test_phase_76a_is_audit_contract_without_runtime_changes(): void
    {
        $contract = $this->contract();

        $this->assertSame('Phase 76A', $contract['phase']);
        $this->assertSame(
            'Saved View Import Apply Service Contract',
            $contract['title']
        );
        $this->assertSame('Phase 75C clean', $contract['baseline']['phase']);
        $this->assertSame('ffefb88', $contract['baseline']['commit']);
        $this->assertSame(
            '1515 passed / 13686 assertions',
            $contract['baseline']['previous_tests']
        );
        $this->assertSame('audit_contract', $contract['scope']['type']);
        $this->assertFalse(
            $contract['scope']['implementation_changes_expected']
        );

        foreach ([
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
        ] as $excludedFile) {
            $this->assertContains(
                $excludedFile,
                $contract['scope']['excluded_implementation_files']
            );
        }
    }

    public function test_phase_76a_historical_contract_records_inline_apply_baseline(): void
    {
        $currentState = $this->contract()['current_state'];

        foreach ([
            'apply_action_validates_csv_payload_in_controller',
            'apply_action_decodes_base64_in_controller',
            'apply_action_manages_temp_file_in_controller',
            'apply_action_reparses_with_csv_parser',
            'apply_action_blocks_invalid_preview_before_writes',
            'row_application_is_private_controller_method',
            'transaction_is_inline_in_controller',
            'valid_row_guard_is_inline_in_controller',
            'duplicate_detection_is_inline_in_controller',
            'default_normalization_is_inline_in_controller',
            'record_creation_is_inline_in_controller',
            'created_and_skipped_counting_is_inline_in_controller',
            'dedicated_import_apply_service_absent',
        ] as $key) {
            $this->assertTrue($currentState[$key], $key);
        }
    }

    public function test_service_identity_api_and_boundaries_are_locked(): void
    {
        $service = $this->contract()['service_contract'];

        $this->assertSame(
            'App\\Services\\ReportSavedViewImportApplyService',
            $service['class']
        );
        $this->assertSame(
            'app/Services/ReportSavedViewImportApplyService.php',
            $service['file']
        );
        $this->assertSame(
            'final_stateless_transactional_service',
            $service['type']
        );
        $this->assertSame([], $service['constructor_dependencies']);
        $this->assertSame(
            'public function apply(User $user, array $rows): array',
            $service['public_api']['apply']['signature']
        );
        $this->assertSame([
            'created' => 'int',
            'skipped' => 'int',
        ], $service['public_api']['apply']['return_shape']);

        foreach ([
            'App\\Models\\ReportSavedView',
            'App\\Models\\User',
            'Illuminate\\Support\\Facades\\DB',
        ] as $collaborator) {
            $this->assertContains(
                $collaborator,
                $service['allowed_collaborators']
            );
        }

        foreach ([
            'request',
            'response',
            'session',
            'authentication_facade',
            'redirects',
            'views',
            'routes',
            'csv_filesystem',
            'base64_payload',
            'csv_parser',
            'version_registry',
        ] as $forbiddenAccess) {
            $this->assertContains(
                $forbiddenAccess,
                $service['must_not_access']
            );
        }
    }

    public function test_row_input_and_result_contracts_are_exact(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            "ReportSavedViewCsvImportParser::parse()['rows']",
            $contract['row_input_contract']['source']
        );
        $this->assertSame([
            'status',
            'report_key',
            'name',
            'is_default',
            'filters',
        ], $contract['row_input_contract']
            ['required_keys_for_valid_rows']);
        $this->assertSame(
            'valid',
            $contract['row_input_contract']['status_policy']['valid_value']
        );
        $this->assertSame(
            'ignored without incrementing skipped',
            $contract['row_input_contract']['status_policy']['non_valid_rows']
        );
        $this->assertSame(
            'نعم',
            $contract['row_input_contract']
                ['is_default_policy']['true_display_value']
        );
        $this->assertSame(
            [],
            $contract['row_input_contract']
                ['filters_policy']['missing_value']
        );
        $this->assertSame(
            'number of newly inserted saved views',
            $contract['result_contract']['created']
        );
        $this->assertSame(
            'number of exact duplicates',
            $contract['result_contract']['skipped']
        );
        $this->assertTrue(
            $contract['result_contract']['invalid_rows_not_counted']
        );
    }

    public function test_transaction_and_duplicate_contracts_are_locked(): void
    {
        $contract = $this->contract();

        foreach ([
            'entire_apply_call_is_single_transaction',
            'return_value_committed_atomically',
            'exceptions_roll_back_all_writes',
            'no_partial_success_outside_transaction',
        ] as $key) {
            $this->assertTrue(
                $contract['transaction_contract'][$key],
                $key
            );
        }

        $this->assertSame(
            'DB::transaction',
            $contract['transaction_contract']['transaction_source']
        );
        $this->assertSame([
            'user_id',
            'report_key',
            'name',
        ], $contract['duplicate_contract']['scope_columns']);
        $this->assertSame(
            'skip without update or overwrite',
            $contract['duplicate_contract']['duplicate_action']
        );

        foreach ([
            'duplicate_increments_skipped',
            'duplicate_does_not_increment_created',
            'existing_filters_preserved',
            'existing_default_state_preserved_until_later_new_default_row',
            'cross_user_records_never_match',
        ] as $key) {
            $this->assertTrue(
                $contract['duplicate_contract'][$key],
                $key
            );
        }
    }

    public function test_default_and_creation_contracts_are_locked(): void
    {
        $contract = $this->contract();

        $this->assertSame(
            'row is_default equals Arabic نعم',
            $contract['default_contract']['default_requested_when']
        );
        $this->assertSame([
            'same user_id',
            'same report_key',
        ], $contract['default_contract']['normalization_scope']);

        foreach ([
            'normalization_happens_before_create',
            'non_default_rows_do_not_change_existing_defaults',
            'last_new_default_row_for_same_report_wins',
            'cross_user_defaults_unchanged',
            'other_report_defaults_unchanged',
        ] as $key) {
            $this->assertTrue(
                $contract['default_contract'][$key],
                $key
            );
        }

        $this->assertSame([
            'user_id' => 'User::$id',
            'report_key' => 'row report_key',
            'name' => 'row name',
            'filters' => 'row filters or empty array',
            'is_default' => 'derived boolean',
        ], $contract['creation_contract']['attributes']);

        foreach ([
            'created_increments_after_successful_create',
            'timestamps_use_model_defaults',
            'no_imported_updated_at_write',
            'no_overwrite_or_upsert',
        ] as $key) {
            $this->assertTrue(
                $contract['creation_contract'][$key],
                $key
            );
        }
    }

    public function test_current_behavior_confirms_duplicate_and_default_semantics(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        ReportSavedView::query()->create([
            'user_id' => $user->id,
            'report_key' => 'profit-loss',
            'name' => 'Existing Contract View',
            'filters' => ['payment_status' => 'unpaid'],
            'is_default' => true,
        ]);

        ReportSavedView::query()->create([
            'user_id' => $otherUser->id,
            'report_key' => 'profit-loss',
            'name' => 'Other User Contract View',
            'filters' => ['payment_status' => 'overdue'],
            'is_default' => true,
        ]);

        $csv = $this->csv([
            [
                'format_version',
                'name',
                'report_label',
                'report_key',
                'is_default',
                'filter_count',
                'filters_summary',
                'filters_payload',
                'updated_at',
            ],
            [
                '1',
                'Existing Contract View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'no',
                '1',
                'ignored summary',
                '{"payment_status":"paid"}',
                '2026-07-16 15:00:00',
            ],
            [
                '1',
                'Imported Contract View',
                'تقرير الأرباح والخسائر',
                'profit-loss',
                'yes',
                '1',
                'ignored summary',
                '{"payment_status":"partial"}',
                '2026-07-16 15:05:00',
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
                . 'وتم تخطي 1 مكرر.'
            );

        $existing = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Existing Contract View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'unpaid'],
            $existing->filters
        );
        $this->assertFalse($existing->is_default);

        $imported = ReportSavedView::query()
            ->where('user_id', $user->id)
            ->where('name', 'Imported Contract View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'partial'],
            $imported->filters
        );
        $this->assertTrue($imported->is_default);

        $other = ReportSavedView::query()
            ->where('user_id', $otherUser->id)
            ->where('name', 'Other User Contract View')
            ->firstOrFail();

        $this->assertSame(
            ['payment_status' => 'overdue'],
            $other->filters
        );
        $this->assertTrue($other->is_default);
    }

    public function test_phase_76b_migration_keeps_http_and_parsing_outside_service(): void
    {
        $migration = $this->contract()['phase_76b_migration_contract'];

        $this->assertSame(
            'use App\\Services\\ReportSavedViewImportApplyService;',
            $migration['controller_import']
        );
        $this->assertStringContainsString(
            'ReportSavedViewImportApplyService',
            $migration['controller_constructor']
        );
        $this->assertStringContainsString(
            '$this->importApplyService->apply(',
            $migration['replace_call']
        );
        $this->assertSame(
            'applySavedViewImportRows',
            $migration['remove_controller_method']
        );

        foreach ([
            'request_validation_changed',
            'base64_handling_changed',
            'temp_file_flow_changed',
            'parser_changed',
            'routes_changed',
            'views_changed',
            'model_changed',
            'database_schema_changed',
            'behavior_change_allowed',
        ] as $key) {
            $this->assertFalse($migration[$key], $key);
        }
    }

    public function test_phase_71_through_75_import_behavior_remains_locked(): void
    {
        $preserved = $this->contract()['preserved_behavior'];

        foreach ([
            'apply_requires_authentication',
            'apply_validates_csv_payload',
            'invalid_base64_message_preserved',
            'temp_file_failure_message_preserved',
            'apply_reparses_before_writes',
            'invalid_header_or_row_blocks_all_writes',
            'transaction_boundary_preserved',
            'authenticated_user_scope_preserved',
            'duplicate_skip_without_overwrite_preserved',
            'created_and_skipped_counts_preserved',
            'default_normalization_preserved',
            'cleaned_filters_preserved',
            'legacy_and_versioned_import_preserved',
            'exact_success_message_preserved',
            'preview_export_bulk_selection_pagination_preserved',
            'phase_69_through_75_contracts_preserved',
        ] as $key) {
            $this->assertTrue($preserved[$key], $key);
        }
    }

    public function test_phase_76b_recommendation_is_transactional_service_extraction(): void
    {
        $recommendation =
            $this->contract()['phase_76b_recommendation'];

        $this->assertSame('Phase 76B', $recommendation['phase']);
        $this->assertSame(
            'Implement Saved View Import Apply Service',
            $recommendation['title']
        );
        $this->assertSame('medium', $recommendation['risk']);
        $this->assertNotEmpty($recommendation['risk_reason']);
        $this->assertCount(
            10,
            $recommendation['implementation_targets']
        );
    }

    public function test_guardrails_forbid_runtime_changes_in_phase_76a(): void
    {
        $guardrails = $this->contract()['guardrails'];

        foreach ([
            'Do not implement the service in Phase 76A.',
            'The future service must be final and stateless.',
            'The future service must accept a User object and parser rows, '
                . 'not an HTTP Request.',
            'The future service must own one transaction around the full '
                . 'row set.',
            'The future service must not parse CSV or inspect format versions.',
            'Invalid rows must remain ignored and must not count as duplicates.',
            'Duplicates must remain scoped by user_id, report_key, and name.',
            'Duplicates must remain skipped without overwrite.',
            'Default normalization must remain scoped to the same user and report.',
            'Filters must come only from parser-cleaned row filters.',
            'Success and failure messages remain controller-owned and exact.',
        ] as $guardrail) {
            $this->assertContains($guardrail, $guardrails);
        }
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
                    . 'phase-76a-saved-view-import-apply-service-'
                    . 'contract.json'
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
