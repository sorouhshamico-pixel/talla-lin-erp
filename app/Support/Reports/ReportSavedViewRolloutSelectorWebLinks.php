<?php

namespace App\Support\Reports;

class ReportSavedViewRolloutSelectorWebLinks
{
    public static function routes(): array
    {
        return [
            'index' => 'reports.saved-view-rollout-selector.index',
            'markdown' => 'reports.saved-view-rollout-selector.markdown',
            'json' => 'reports.saved-view-rollout-selector.json',
            'candidates' => 'reports.saved-view-candidates.index',
            'diagnostics' => 'reports.saved-view-diagnostics.index',
        ];
    }

    public static function labels(): array
    {
        return [
            'index' => 'Rollout Selector Page',
            'markdown' => 'Rollout Selector Markdown Export',
            'json' => 'Rollout Selector JSON Export',
            'candidates' => 'Candidate Scanner Page',
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
            'php artisan reports:saved-view-rollout-selector',
            'php artisan reports:saved-view-rollout-selector --json',
            'php artisan reports:saved-view-candidates',
            'php artisan reports:saved-view-diagnostics',
        ];
    }

    public static function workflowSteps(): array
    {
        return [
            'Open the rollout selector page.',
            'Review the next candidate and its priority score.',
            'Open the candidate scanner for full candidate context.',
            'Open diagnostics before implementation to confirm registry health.',
            'Implement saved view controls for the selected report.',
            'Run diagnostics again after the rollout.',
        ];
    }
}
