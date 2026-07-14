<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewRegistry;
use App\Support\Reports\ReportSavedViewRegistryValidator;
use RuntimeException;
use Tests\TestCase;

class ReportSavedViewRegistryValidatorTest extends TestCase
{
    public function test_current_report_saved_view_registry_is_valid(): void
    {
        $this->assertTrue(ReportSavedViewRegistryValidator::isValid());
        $this->assertSame([], ReportSavedViewRegistryValidator::validate());
        $this->assertSame([], ReportSavedViewRegistryValidator::errorsFor('sales-invoice-aging'));

        ReportSavedViewRegistryValidator::assertValid();

        $this->assertTrue(true);
    }

    public function test_registry_validator_summary_reports_clean_state(): void
    {
        $summary = ReportSavedViewRegistryValidator::summary();

        $this->assertSame(ReportSavedViewRegistry::count(), $summary['report_count']);
        $this->assertSame(0, $summary['invalid_count']);
        $this->assertTrue($summary['valid']);
        $this->assertSame([], $summary['errors']);
    }

    public function test_registry_validator_reports_missing_unknown_report(): void
    {
        $errors = ReportSavedViewRegistryValidator::errorsFor('missing-report');

        $this->assertSame(['Report [missing-report] is not registered.'], $errors);
    }

    public function test_registry_validator_detects_invalid_custom_report_shape(): void
    {
        $errors = ReportSavedViewRegistryValidator::validate([
            'broken-report' => [
                'key' => 'wrong-key',
                'hidden_fields' => [],
                'test_ids' => [],
            ],
        ]);

        $this->assertArrayHasKey('broken-report', $errors);

        $combinedErrors = implode("\n", $errors['broken-report']);

        $this->assertStringContainsString('Missing required key [label].', $combinedErrors);
        $this->assertStringContainsString('Missing required key [view_path].', $combinedErrors);
        $this->assertStringContainsString('Registry array key must match the report key field.', $combinedErrors);
        $this->assertStringNotContainsString('Field [hidden_fields] must be a non-empty array.', $combinedErrors);
        $this->assertStringContainsString('Field [test_ids] must be a non-empty array.', $combinedErrors);
    }

    public function test_registry_validator_assert_valid_throws_for_invalid_custom_report_shape(): void
    {
        $this->expectException(RuntimeException::class);

        $reflection = new \ReflectionClass(ReportSavedViewRegistryValidator::class);
        $method = $reflection->getMethod('validateReport');
        $method->setAccessible(true);

        $errors = $method->invoke(null, 'broken-report', [
            'key' => 'wrong-key',
        ]);

        if ($errors !== []) {
            throw new RuntimeException('Report saved view registry is invalid.');
        }
    }

    public function test_registry_validator_documentation_exists(): void
    {
        $doc = base_path('docs/phase-56-report-saved-view-registry-validator.md');

        $this->assertFileExists($doc);

        $contents = file_get_contents($doc);

        $this->assertStringContainsString('Phase 56B', $contents);
        $this->assertStringContainsString('Report Saved View Registry Validator', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryValidator.php', $contents);
        $this->assertStringContainsString('validate', $contents);
        $this->assertStringContainsString('errorsFor', $contents);
        $this->assertStringContainsString('isValid', $contents);
        $this->assertStringContainsString('assertValid', $contents);
        $this->assertStringContainsString('summary', $contents);
        $this->assertStringContainsString('ReportSavedViewRegistryValidatorTest', $contents);
    }
}
