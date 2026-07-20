<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase90ARetentionExecutionHistoryExportDateRangeShortcutsContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-90a-retention-execution-history-export-date-range-shortcuts-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-90a-retention-execution-history-export-date-range-shortcuts-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 90A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '2e3be6d6b1a686836583b2b455a98eb2e65cfc6c',
            $document['baseline']['commit']
        );
        $this->assertSame(1847, $document['baseline']['tests']);
        $this->assertSame(16733, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ([
            'runtime_changes_expected',
            'database_changes_expected',
            'migration_changes_expected',
            'model_changes_expected',
            'service_changes_expected',
            'controller_changes_expected',
            'route_changes_expected',
            'view_changes_expected',
        ] as $key) {
            $this->assertFalse($scope[$key], $key);
        }

        $this->assertTrue($scope['documentation_and_tests_only']);
    }

    public function test_shortcut_keys_labels_and_windows_are_locked(): void
    {
        $shortcuts = $this->document()
            ['date_range_shortcuts_contract']['shortcuts'];

        $this->assertSame(
            [
                'today',
                'last_7_days',
                'last_30_days',
                'this_month',
                'previous_month',
                'clear_dates',
            ],
            array_column($shortcuts, 'key')
        );

        $this->assertSame(
            [
                'Today',
                'Last 7 days',
                'Last 30 days',
                'This month',
                'Previous month',
                'Clear date range',
            ],
            array_column($shortcuts, 'label')
        );
    }

    public function test_time_semantics_and_implementation_model_are_locked(): void
    {
        $contract = $this->document()
            ['date_range_shortcuts_contract'];

        $time = $contract['time_semantics'];

        $this->assertSame('UTC', $time['timezone']);
        $this->assertTrue($time['started_from_inclusive']);
        $this->assertTrue($time['started_to_inclusive']);
        $this->assertTrue($time['generated_server_side']);
        $this->assertSame(
            "Illuminate\\Support\\Carbon::now('UTC')",
            $time['current_time_source']
        );
        $this->assertSame('Y-m-d\\TH:i', $time['format']);

        $model = $contract['implementation_model'];

        $this->assertTrue($model['fixed_application_shortcuts']);
        $this->assertFalse($model['database_persistence']);
        $this->assertFalse($model['session_persistence']);
        $this->assertFalse($model['cache_persistence']);
        $this->assertFalse($model['new_model_required']);
        $this->assertFalse($model['new_service_required']);
        $this->assertFalse($model['new_controller_required']);
        $this->assertFalse($model['new_route_required']);
    }

    public function test_behavior_presentation_compatibility_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['date_range_shortcuts_contract'];

        foreach ($contract['behavior'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['presentation'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        $this->assertFalse(
            $contract['planned_implementation']
                ['controller_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['service_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['route_changes_expected']
        );
        $this->assertFalse(
            $contract['planned_implementation']
                ['database_changes_expected']
        );

        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 90B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-90a-retention-execution-history-export-date-range-shortcuts-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
