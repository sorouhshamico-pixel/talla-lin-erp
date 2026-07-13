<?php

namespace App\Support\Reports;

use Illuminate\Support\Facades\File;

class ReportSavedViewRolloutTarget
{
    public const LOCK_PATH = 'docs/phase-63-report-saved-view-rollout-target.json';

    public const INSPECTION_PATH = 'docs/phase-63-report-saved-view-rollout-target-inspection.json';

    public static function lockPath(): string
    {
        return base_path(self::LOCK_PATH);
    }

    public static function inspectionPath(): string
    {
        return base_path(self::INSPECTION_PATH);
    }

    public static function hasLock(): bool
    {
        return File::exists(self::lockPath());
    }

    public static function hasInspection(): bool
    {
        return File::exists(self::inspectionPath());
    }

    public static function lock(): array
    {
        if (! self::hasLock()) {
            return [];
        }

        return json_decode(File::get(self::lockPath()), true) ?: [];
    }

    public static function inspection(): array
    {
        if (! self::hasInspection()) {
            return [];
        }

        return json_decode(File::get(self::inspectionPath()), true) ?: [];
    }

    public static function target(): ?array
    {
        $lock = self::lock();

        return is_array($lock['selected_target'] ?? null)
            ? $lock['selected_target']
            : null;
    }

    public static function key(): ?string
    {
        return self::target()['key'] ?? null;
    }

    public static function viewPath(): ?string
    {
        return self::target()['view_path'] ?? null;
    }

    public static function priorityScore(): ?int
    {
        $score = self::target()['priority_score'] ?? null;

        return is_numeric($score) ? (int) $score : null;
    }

    public static function candidateFilterFields(): array
    {
        $inspection = self::inspection();

        return is_array($inspection['candidate_filter_fields'] ?? null)
            ? $inspection['candidate_filter_fields']
            : [];
    }

    public static function routeNames(): array
    {
        $inspection = self::inspection();

        return is_array($inspection['route_names'] ?? null)
            ? $inspection['route_names']
            : [];
    }

    public static function includeNames(): array
    {
        $inspection = self::inspection();

        return is_array($inspection['include_names'] ?? null)
            ? $inspection['include_names']
            : [];
    }

    public static function recommendedConfigPartial(): ?string
    {
        return self::inspection()['recommended_config_partial'] ?? null;
    }

    public static function recommendedConfigPartialPath(): ?string
    {
        return self::inspection()['recommended_config_partial_path'] ?? null;
    }

    public static function viewExists(): bool
    {
        $viewPath = self::viewPath();

        return $viewPath !== null && File::exists(base_path($viewPath));
    }

    public static function summary(): array
    {
        return [
            'has_lock' => self::hasLock(),
            'has_inspection' => self::hasInspection(),
            'key' => self::key(),
            'view_path' => self::viewPath(),
            'priority_score' => self::priorityScore(),
            'view_exists' => self::viewExists(),
            'candidate_filter_fields' => self::candidateFilterFields(),
            'route_names' => self::routeNames(),
            'include_names' => self::includeNames(),
            'recommended_config_partial' => self::recommendedConfigPartial(),
            'recommended_config_partial_path' => self::recommendedConfigPartialPath(),
        ];
    }

    public static function markdownLines(): array
    {
        $summary = self::summary();

        $lines = [
            '# Report Saved View Rollout Target',
            '',
            '## Summary',
            '',
            '- Has lock: '.($summary['has_lock'] ? 'yes' : 'no'),
            '- Has inspection: '.($summary['has_inspection'] ? 'yes' : 'no'),
            '- Key: '.($summary['key'] ?? 'none'),
            '- View path: '.($summary['view_path'] ?? 'none'),
            '- Priority score: '.($summary['priority_score'] ?? 'none'),
            '- View exists: '.($summary['view_exists'] ? 'yes' : 'no'),
            '- Recommended config partial: '.($summary['recommended_config_partial'] ?? 'none'),
            '- Recommended config partial path: '.($summary['recommended_config_partial_path'] ?? 'none'),
            '',
            '## Candidate Filter Fields',
            '',
        ];

        foreach ($summary['candidate_filter_fields'] as $field) {
            $lines[] = '- '.$field;
        }

        if ($summary['candidate_filter_fields'] === []) {
            $lines[] = '- none';
        }

        $lines[] = '';
        $lines[] = '## Route Names';
        $lines[] = '';

        foreach ($summary['route_names'] as $routeName) {
            $lines[] = '- '.$routeName;
        }

        if ($summary['route_names'] === []) {
            $lines[] = '- none';
        }

        return $lines;
    }

    public static function markdown(): string
    {
        return implode(PHP_EOL, self::markdownLines()).PHP_EOL;
    }
}
