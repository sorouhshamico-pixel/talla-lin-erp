<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportSavedViewPhase119CRetentionExecutionHistoryExportSummaryCacheDiagnosticsRefreshAuditMetricsHealthPresentationManualRefreshSuccessCounterFinalizationTest extends TestCase
{
    private const JSON_PATH = 'docs/phase-119c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-counter-finalization.json';

    public function test_documents_and_baseline_are_locked(): void
    {
        $this->assertFileExists(base_path(self::JSON_PATH));
        $this->assertFileExists(base_path('docs/phase-119c-retention-execution-history-export-summary-cache-diagnostics-refresh-audit-metrics-health-presentation-manual-refresh-success-counter-finalization.md'));

        $document = $this->document();
        $this->assertSame('Phase 119C', $document['phase']);
        $this->assertSame('finalization', $document['type']);
        $this->assertSame('f4f609d774e12bc780215d61c7ffbbc6d23cef0b', $document['baseline']['commit']);
        $this->assertSame(2335, $document['baseline']['tests']);
        $this->assertSame(23681, $document['baseline']['assertions']);
    }

    public function test_scope_is_documentation_and_tests_only(): void
    {
        $scope = $this->document()['scope'];
        foreach ($scope as $key => $value) {
            $key === 'documentation_and_tests_only'
                ? $this->assertTrue($value, $key)
                : $this->assertFalse($value, $key);
        }
    }

    public function test_counter_classification_and_order_are_locked(): void
    {
        $locked = $this->document()['locked_implementation'];

        $this->assertSame('retention-audit-metrics-health-manual-refresh-successes', $locked['element']['id']);
        $this->assertSame('manualRefreshSuccesses', $locked['state']['variable']);
        $this->assertSame(999, $locked['state']['maximum']);
        $this->assertFalse($locked['state']['persistent_storage_used']);
        $this->assertSame('renderManualRefreshSuccesses', $locked['helpers']['renderer']);
        $this->assertSame('recordManualRefreshSuccess', $locked['helpers']['recorder']);

        $this->assertTrue($locked['classification']['validated_healthy_counts']);
        $this->assertTrue($locked['classification']['validated_unhealthy_counts']);

        foreach (['http_error_counts','network_failure_counts','json_parse_failure_counts','payload_validation_failure_counts','initial_automatic_request_counts','ignored_concurrent_manual_request_counts'] as $key) {
            $this->assertFalse($locked['classification'][$key], $key);
        }

        foreach ($locked['update_order'] as $key => $value) {
            $this->assertTrue($value, $key);
        }
    }

    public function test_legacy_compatibility_scope_workflow_and_next_phase_are_locked(): void
    {
        $document = $this->document();
        $locked = $document['locked_implementation'];

        $this->assertTrue($locked['legacy_contract']['preserved']);
        $this->assertSame('const loadHealth = async () => {', $locked['legacy_contract']['load_health_signature']);
        $this->assertSame("refresh.addEventListener('click', loadHealth);", $locked['legacy_contract']['refresh_listener']);
        $this->assertSame('loadHealth();', $locked['legacy_contract']['initial_load']);

        foreach ($locked['compatibility'] as $key => $value) {
            $this->assertFalse($value, $key);
        }

        $this->assertTrue($locked['implementation_scope']['partial_modified']);
        $this->assertTrue($locked['implementation_scope']['phase_119b_test_added']);
        $this->assertSame(2, $locked['implementation_scope']['maximum_modified_files']);
        $this->assertSame('once before commit', $document['workflow']['full_suite_runs']);
        $this->assertFalse($document['workflow']['post_commit_full_suite']);
        $this->assertSame('Phase 120A', $document['next_recommendation']['phase']);
    }

    private function document(): array
    {
        $document = json_decode(file_get_contents(base_path(self::JSON_PATH)), true);
        $this->assertIsArray($document);
        return $document;
    }
}
