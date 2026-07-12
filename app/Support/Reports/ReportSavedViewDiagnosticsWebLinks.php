<?php

namespace App\Support\Reports;

class ReportSavedViewDiagnosticsWebLinks
{
    public static function routes(): array
    {
        return [
            'index' => 'reports.saved-view-diagnostics.index',
            'markdown' => 'reports.saved-view-diagnostics.markdown',
            'json' => 'reports.saved-view-diagnostics.json',
        ];
    }

    public static function snapshotActionRoutes(): array
    {
        return [
            'write_markdown' => 'reports.saved-view-diagnostics.snapshots.markdown',
            'write_json' => 'reports.saved-view-diagnostics.snapshots.json',
            'prune' => 'reports.saved-view-diagnostics.snapshots.prune',
        ];
    }

    public static function labels(): array
    {
        return [
            'index' => 'Diagnostics Page',
            'markdown' => 'Markdown Export',
            'json' => 'JSON Export',
        ];
    }

    public static function snapshotActionLabels(): array
    {
        return [
            'write_markdown' => 'Write Markdown Snapshot',
            'write_json' => 'Write JSON Snapshot',
            'prune' => 'Prune Snapshots',
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

    public static function snapshotActionItems(): array
    {
        $labels = self::snapshotActionLabels();

        return array_map(
            fn (string $key, string $route): array => [
                'key' => $key,
                'label' => $labels[$key],
                'route' => $route,
            ],
            array_keys(self::snapshotActionRoutes()),
            self::snapshotActionRoutes()
        );
    }

    public static function allRoutes(): array
    {
        return array_merge(self::routes(), self::snapshotActionRoutes());
    }

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
