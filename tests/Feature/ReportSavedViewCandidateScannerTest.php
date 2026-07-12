<?php

namespace Tests\Feature;

use App\Support\Reports\ReportSavedViewCandidateScanner;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReportSavedViewCandidateScannerTest extends TestCase
{
    public function test_candidate_scanner_returns_report_view_candidates(): void
    {
        $candidates = ReportSavedViewCandidateScanner::candidates();

        $this->assertIsArray($candidates);
        $this->assertNotEmpty($candidates);

        foreach ($candidates as $candidate) {
            $this->assertArrayHasKey('key', $candidate);
            $this->assertArrayHasKey('view_path', $candidate);
            $this->assertArrayHasKey('registered', $candidate);
            $this->assertArrayHasKey('has_get_form', $candidate);
            $this->assertArrayHasKey('has_filter_terms', $candidate);
            $this->assertArrayHasKey('has_saved_view_controls', $candidate);
            $this->assertArrayHasKey('priority_score', $candidate);

            $this->assertStringStartsWith('resources/views/reports/', $candidate['view_path']);
            $this->assertStringEndsWith('.blade.php', $candidate['view_path']);
            $this->assertStringNotContainsString('/partials/', $candidate['view_path']);
            $this->assertNotSame('saved-view-diagnostics', $candidate['key']);
        }
    }

    public function test_candidate_scanner_includes_sales_invoice_aging_as_registered_reference(): void
    {
        $candidates = collect(ReportSavedViewCandidateScanner::candidates());

        $candidate = $candidates->firstWhere('key', 'sales-invoice-aging');

        $this->assertNotNull($candidate);
        $this->assertTrue($candidate['registered']);
        $this->assertTrue($candidate['has_saved_view_controls']);
        $this->assertSame('resources/views/reports/sales-invoice-aging.blade.php', $candidate['view_path']);
    }

    public function test_candidate_scanner_summary_is_consistent(): void
    {
        $summary = ReportSavedViewCandidateScanner::summary();
        $candidates = ReportSavedViewCandidateScanner::candidates();

        $this->assertSame(count($candidates), $summary['candidate_count']);
        $this->assertSame(
            count(ReportSavedViewCandidateScanner::registeredCandidates()),
            $summary['registered_count']
        );
        $this->assertSame(
            count(ReportSavedViewCandidateScanner::unregisteredCandidates()),
            $summary['unregistered_count']
        );

        $this->assertContains('sales-invoice-aging', $summary['registered_keys']);
    }

    public function test_candidate_scanner_markdown_contains_summary_and_candidates(): void
    {
        $markdown = ReportSavedViewCandidateScanner::markdown();

        $this->assertStringContainsString('# Report Saved View Candidate Scanner', $markdown);
        $this->assertStringContainsString('## Summary', $markdown);
        $this->assertStringContainsString('- Candidate count:', $markdown);
        $this->assertStringContainsString('## Candidates', $markdown);
        $this->assertStringContainsString('### sales-invoice-aging', $markdown);
    }

    public function test_saved_view_candidates_command_outputs_markdown(): void
    {
        $exitCode = Artisan::call('reports:saved-view-candidates');

        $this->assertSame(0, $exitCode);

        $output = trim(Artisan::output());

        $this->assertStringContainsString('# Report Saved View Candidate Scanner', $output);
        $this->assertStringContainsString('### sales-invoice-aging', $output);
    }

    public function test_saved_view_candidates_command_outputs_json(): void
    {
        $exitCode = Artisan::call('reports:saved-view-candidates', [
            '--json' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $decoded = json_decode(Artisan::output(), true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('summary', $decoded);
        $this->assertArrayHasKey('candidates', $decoded);
        $this->assertContains('sales-invoice-aging', $decoded['summary']['registered_keys']);
    }

    public function test_phase_61a_candidate_scanner_is_documented(): void
    {
        $doc = base_path('docs/phase-61-report-saved-view-candidate-scanner.md');
        $scanner = base_path('app/Support/Reports/ReportSavedViewCandidateScanner.php');
        $routes = base_path('routes/console.php');

        $this->assertFileExists($doc);
        $this->assertFileExists($scanner);

        $docContents = file_get_contents($doc);
        $routesContents = file_get_contents($routes);

        $this->assertStringContainsString('Phase 61A', $docContents);
        $this->assertStringContainsString('Report Saved View Candidate Scanner', $docContents);
        $this->assertStringContainsString('php artisan reports:saved-view-candidates', $docContents);
        $this->assertStringContainsString('ReportSavedViewCandidateScannerTest', $docContents);

        $this->assertStringContainsString('reports:saved-view-candidates', $routesContents);
    }
}
