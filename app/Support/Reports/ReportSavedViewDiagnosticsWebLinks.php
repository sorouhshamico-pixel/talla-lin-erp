<?php

namespace App\Support\Reports;

class ReportSavedViewDiagnosticsWebLinks
{
    /**
     * @return array<string, string>
     */
    public static function routes(): array
    {
        return [
            'index' => 'reports.saved-view-diagnostics.index',
            'markdown' => 'reports.saved-view-diagnostics.markdown',
            'json' => 'reports.saved-view-diagnostics.json',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'index' => 'Diagnostics Page',
            'markdown' => 'Markdown Export',
            'json' => 'JSON Export',
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
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

    /**
     * @return array<int, string>
     */
    public static function commandExamples(): array
    {
        return [
            'php artisan reports:saved-view-diagnostics',
            'php artisan reports:saved-view-diagnostics --json',
            'php artisan reports:saved-view-diagnostics --write',
            'php artisan reports:saved-view-diagnostics --write --format=json',
            'php artisan reports:saved-view-diagnostics --prune',
            'php artisan reports:saved-view-diagnostics --prune --include-manifest',
        ];
    }
}
