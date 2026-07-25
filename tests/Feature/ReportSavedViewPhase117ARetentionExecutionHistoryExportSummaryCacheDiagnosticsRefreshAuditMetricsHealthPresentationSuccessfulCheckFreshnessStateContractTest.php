<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase117ARetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationSuccessfulCheckFreshnessStateContractTest
    extends TestCase
{
    private const JSON_PATH =
        'docs/'
        . 'phase-117a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-freshness-state-contract.json';

    public function test_contract_documents_exist_and_baseline_is_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path(
            'docs/'
            . 'phase-117a-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-successful-check-freshness-state-contract.md'
        ));

        $document = $this->document();

        $this->assertSame('Phase 117A', $document['phase']);
        $this->assertSame('contract', $document['type']);
        $this->assertSame(
            '1fa4460c3efb43ea480f808dccfe12cfb5561f0b',
            $document['baseline']['commit']
        );
        $this->assertSame(2290, $document['baseline']['tests']);
        $this->assertSame(23018, $document['baseline']['assertions']);
    }

    public function test_phase_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];

        foreach ($scope as $key => $value) {
            if ($key === 'documentation_and_tests_only') {
                $this->assertTrue($value, $key);

                continue;
            }

            $this->assertFalse($value, $key);
        }
    }

    public function test_element_states_source_and_update_rules_are_locked(): void
    {
        $contract = $this->document()['freshness_state_contract'];

        $this->assertSame(
            'retention-audit-metrics-health-successful-check-freshness',
            $contract['element']['id']
        );
        $this->assertSame(
            'Successful check freshness:',
            $contract['element']['prefix']
        );
        $this->assertSame(
            'Unavailable',
            $contract['element']['initial_text']
        );

        $this->assertSame(
            'Fresh',
            $contract['state_values']['fresh']['text']
        );
        $this->assertSame(
            14,
            $contract['state_values']['fresh']['maximum_age_minutes_inclusive']
        );
        $this->assertSame(
            15,
            $contract['state_values']['stale']['minimum_age_minutes']
        );
        $this->assertSame(
            'Unavailable',
            $contract['state_values']['unavailable']['text']
        );

        $this->assertSame(
            'lastSuccessfulCheckAt',
            $contract['source']['state_variable']
        );
        $this->assertFalse(
            $contract['source']['rendered_age_text_parsed']
        );

        $this->assertFalse(
            $contract['update_rules']['request_start_clears_previous_value']
        );
        $this->assertFalse(
            $contract['update_rules']['validated_unhealthy_response_updates']
        );
        $this->assertFalse(
            $contract['update_rules']['ignored_concurrent_request_updates']
        );
        $this->assertFalse(
            $contract['update_rules']['background_timer_updates']
        );
        $this->assertFalse(
            $contract['update_rules']['polling_updates']
        );

        foreach ([
            'validated_healthy_response_updates',
            'updates_with_same_completed_date_as_successful_check',
            'updates_once_per_validated_healthy_request',
        ] as $key) {
            $this->assertTrue($contract['update_rules'][$key], $key);
        }
    }

    public function test_visual_accessibility_privacy_compatibility_and_workflow_are_locked(): void
    {
        $document = $this->document();
        $contract = $document['freshness_state_contract'];

        $this->assertSame(
            'data-freshness-state',
            $contract['visual_state']['data_attribute']
        );
        $this->assertSame(
            ['fresh', 'stale', 'unavailable'],
            $contract['visual_state']['allowed_values']
        );
        $this->assertSame(
            'unavailable',
            $contract['visual_state']['initial_value']
        );
        $this->assertFalse(
            $contract['visual_state']['css_class_changes_required']
        );
        $this->assertFalse(
            $contract['visual_state']['panel_health_state_changed']
        );
        $this->assertFalse(
            $contract['visual_state']['indicator_state_changed']
        );

        foreach ($contract['accessibility'] as $key => $value) {
            $this->assertTrue($value, $key);
        }

        foreach ($contract['privacy'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        foreach ($contract['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertSame(
            2,
            $contract['planned_implementation']['maximum_modified_files']
        );
        $this->assertSame(
            'once before commit',
            $document['workflow']['full_suite_runs']
        );
        $this->assertFalse(
            $document['workflow']['post_commit_full_suite']
        );
        $this->assertSame(
            'Phase 117B',
            $document['next_recommendation']['phase']
        );
    }

    private function document(): array
    {
        $document = json_decode(
            file_get_contents(base_path(self::JSON_PATH)),
            true
        );

        $this->assertIsArray($document);

        return $document;
    }
}
