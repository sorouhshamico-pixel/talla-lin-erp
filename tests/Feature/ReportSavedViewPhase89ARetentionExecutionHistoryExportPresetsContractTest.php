<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase89ARetentionExecutionHistoryExportPresetsContractTest
    extends TestCase
{
    public function test_contract_documents_exist_and_are_valid(): void
    {
        $jsonPath = base_path(
            'docs/'
            . 'phase-89a-retention-execution-history-export-presets-contract.json'
        );
        $markdownPath = base_path(
            'docs/'
            . 'phase-89a-retention-execution-history-export-presets-contract.md'
        );

        $this->assertFileExists($jsonPath);
        $this->assertFileExists($markdownPath);

        $document = json_decode(
            file_get_contents($jsonPath),
            true
        );

        $this->assertIsArray($document);
        $this->assertSame('Phase 89A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            'c1efd0de98516595c8175d46405e87a7692c8cfc',
            $document['baseline']['commit']
        );
        $this->assertSame(1832, $document['baseline']['tests']);
        $this->assertSame(16574, $document['baseline']['assertions']);
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

    public function test_fixed_non_persistent_implementation_model_is_locked(): void
    {
        $model = $this->document()['presets_contract']
            ['implementation_model'];

        $this->assertTrue($model['fixed_application_presets']);
        $this->assertFalse($model['user_created_presets']);
        $this->assertFalse($model['database_persistence']);
        $this->assertFalse($model['session_persistence']);
        $this->assertFalse($model['cache_persistence']);
        $this->assertFalse($model['new_model_required']);
        $this->assertFalse($model['new_service_required']);
        $this->assertFalse($model['new_controller_required']);
        $this->assertFalse($model['new_route_required']);
    }

    public function test_preset_keys_labels_and_filters_are_locked(): void
    {
        $presets = $this->document()['presets_contract']['presets'];

        $this->assertSame(
            [
                'all',
                'failed',
                'conflicted',
                'manual',
                'scheduled',
                'command',
            ],
            array_column($presets, 'key')
        );

        $indexed = collect($presets)->keyBy('key');

        $this->assertSame([], $indexed['all']['filters']);
        $this->assertSame(
            ['status' => 'failed'],
            $indexed['failed']['filters']
        );
        $this->assertSame(
            ['status' => 'conflicted'],
            $indexed['conflicted']['filters']
        );
        $this->assertSame(
            ['type' => 'manual_execution'],
            $indexed['manual']['filters']
        );
        $this->assertSame(
            ['type' => 'scheduled_execution'],
            $indexed['scheduled']['filters']
        );
        $this->assertSame(
            ['type' => 'command_execution'],
            $indexed['command']['filters']
        );
    }

    public function test_behavior_presentation_compatibility_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['presets_contract'];

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
            'Phase 89B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(
                base_path(
                    'docs/'
                    . 'phase-89a-retention-execution-history-export-presets-contract.json'
                )
            ),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
