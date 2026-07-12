<?php

namespace App\Support\Reports;

class ReportSavedViewRegistryDiagnosticReport
{
    /**
     * @return array<string, mixed>
     */
    public static function build(): array
    {
        $summary = ReportSavedViewRegistryValidator::summary();
        $diagnostics = ReportSavedViewRegistryValidator::diagnostics();

        return [
            'title' => 'Report Saved View Registry Diagnostic Report',
            'summary' => $summary,
            'rows' => $diagnostics,
            'valid_report_keys' => ReportSavedViewRegistryValidator::validReportKeys(),
            'invalid_reports' => ReportSavedViewRegistryValidator::invalidReports(),
            'generated_from' => [
                ReportSavedViewRegistry::class,
                ReportSavedViewRegistryValidator::class,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function rows(): array
    {
        return self::build()['rows'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(): array
    {
        return self::build()['summary'];
    }

    public static function isHealthy(): bool
    {
        return (bool) self::summary()['valid'];
    }

    /**
     * @return array<int, string>
     */
    public static function validReportKeys(): array
    {
        return self::build()['valid_report_keys'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function invalidReports(): array
    {
        return self::build()['invalid_reports'];
    }

    public static function markdown(): string
    {
        $report = self::build();
        $summary = $report['summary'];

        $lines = [
            '# Report Saved View Registry Diagnostic Report',
            '',
            '## Summary',
            '',
            '- Report count: '.$summary['report_count'],
            '- Invalid count: '.$summary['invalid_count'],
            '- Valid: '.($summary['valid'] ? 'yes' : 'no'),
            '',
            '## Reports',
            '',
        ];

        foreach ($report['rows'] as $row) {
            $lines[] = '### '.$row['key'];
            $lines[] = '';
            $lines[] = '- Label: '.($row['label'] ?? '-');
            $lines[] = '- Valid: '.($row['valid'] ? 'yes' : 'no');
            $lines[] = '- Error count: '.$row['error_count'];
            $lines[] = '- View path: '.($row['view_path'] ?? '-');
            $lines[] = '- Config partial path: '.($row['config_partial_path'] ?? '-');
            $lines[] = '- Index route: '.($row['index_route'] ?? '-');
            $lines[] = '- Saved view store route: '.($row['saved_view_store_route'] ?? '-');
            $lines[] = '- Hidden fields: '.implode(', ', $row['hidden_fields']);
            $lines[] = '';

            if ($row['errors'] !== []) {
                $lines[] = 'Errors:';
                $lines[] = '';

                foreach ($row['errors'] as $error) {
                    $lines[] = '- '.$error;
                }

                $lines[] = '';
            }
        }

        return implode("\n", $lines);
    }
}
