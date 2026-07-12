<?php

namespace App\Support\Reports;

class ReportSavedViewCandidateScannerWebLinks
{
    public static function routes(): array
    {
        return [
            'index' => 'reports.saved-view-candidates.index',
            'markdown' => 'reports.saved-view-candidates.markdown',
            'json' => 'reports.saved-view-candidates.json',
            'diagnostics' => 'reports.saved-view-diagnostics.index',
        ];
    }

    public static function labels(): array
    {
        return [
            'index' => 'Candidate Scanner Page',
            'markdown' => 'Candidate Scanner Markdown Export',
            'json' => 'Candidate Scanner JSON Export',
            'diagnostics' => 'Diagnostics Page',
        ];
    }

    public static function items(): array
    {
        $labels = self::labels();

        return array_map(
            fn (string $key, string $route): array => [
                'key' => $key,
                'label' => $labels[$key],
                'route' => $route,
            ],
            array_keys(self::routes()),
            self::routes()
        );
    }

    public static function commandExamples(): array
    {
        return [
            'php artisan reports:saved-view-candidates',
            'php artisan reports:saved-view-candidates --json',
        ];
    }
}
