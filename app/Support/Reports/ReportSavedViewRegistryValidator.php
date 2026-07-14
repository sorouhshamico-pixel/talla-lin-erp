<?php

namespace App\Support\Reports;

use Illuminate\Support\Facades\Route;
use RuntimeException;

class ReportSavedViewRegistryValidator
{
    /**
     * @param  array<string, array<string, mixed>>|null  $reports
     * @return array<string, array<int, string>>
     */
    public static function validate(?array $reports = null): array
    {
        $reports ??= ReportSavedViewRegistry::reports();

        $errors = [];

        foreach ($reports as $registryKey => $report) {
            $reportKey = is_string($registryKey) ? $registryKey : (string) $registryKey;
            $errors[$reportKey] = self::validateReport($reportKey, $report);
        }

        return array_filter($errors);
    }

    /**
     * @return array<int, string>
     */
    public static function errorsFor(string $key): array
    {
        $report = ReportSavedViewRegistry::find($key);

        if (! $report) {
            return ["Report [{$key}] is not registered."];
        }

        return self::validateReport($key, $report);
    }

    public static function isValid(): bool
    {
        return self::validate() === [];
    }

    public static function assertValid(): void
    {
        $errors = self::validate();

        if ($errors !== []) {
            throw new RuntimeException('Report saved view registry is invalid: '.json_encode($errors, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(): array
    {
        $reports = ReportSavedViewRegistry::reports();
        $errors = self::validate($reports);

        return [
            'report_count' => count($reports),
            'invalid_count' => count($errors),
            'valid' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $reports
     * @return array<int, array<string, mixed>>
     */
    public static function diagnostics(?array $reports = null): array
    {
        $reports ??= ReportSavedViewRegistry::reports();

        $errors = self::validate($reports);
        $rows = [];

        foreach ($reports as $registryKey => $report) {
            $reportKey = is_string($registryKey) ? $registryKey : (string) $registryKey;
            $reportErrors = $errors[$reportKey] ?? [];

            $rows[] = [
                'key' => $reportKey,
                'label' => is_string($report['label'] ?? null) ? $report['label'] : null,
                'valid' => $reportErrors === [],
                'error_count' => count($reportErrors),
                'errors' => $reportErrors,
                'view_path' => is_string($report['view_path'] ?? null) ? $report['view_path'] : null,
                'config_partial_path' => is_string($report['config_partial_path'] ?? null) ? $report['config_partial_path'] : null,
                'index_route' => is_string($report['index_route'] ?? null) ? $report['index_route'] : null,
                'saved_view_store_route' => is_string($report['saved_view_store_route'] ?? null) ? $report['saved_view_store_route'] : null,
                'hidden_fields' => is_array($report['hidden_fields'] ?? null) ? $report['hidden_fields'] : [],
                'test_ids' => is_array($report['test_ids'] ?? null) ? $report['test_ids'] : [],
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, array<string, mixed>>|null  $reports
     * @return array<int, array<string, mixed>>
     */
    public static function invalidReports(?array $reports = null): array
    {
        return array_values(array_filter(
            self::diagnostics($reports),
            fn (array $row): bool => $row['valid'] === false
        ));
    }

    /**
     * @return array<int, string>
     */
    public static function validReportKeys(): array
    {
        return array_values(array_map(
            fn (array $row): string => $row['key'],
            array_filter(
                self::diagnostics(),
                fn (array $row): bool => $row['valid'] === true
            )
        ));
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, string>
     */
    private static function validateReport(string $registryKey, array $report): array
    {
        $errors = [];

        $requiredKeys = [
            'key',
            'label',
            'view',
            'view_path',
            'index_route',
            'export_route',
            'saved_view_store_route',
            'config_partial',
            'config_partial_path',
            'hidden_fields',
            'test_ids',
        ];

        foreach ($requiredKeys as $requiredKey) {
            if (! array_key_exists($requiredKey, $report)) {
                $errors[] = "Missing required key [{$requiredKey}].";
            }
        }

        if (($report['key'] ?? null) !== $registryKey) {
            $errors[] = 'Registry array key must match the report key field.';
        }

        foreach (['label', 'view', 'view_path', 'index_route', 'export_route', 'saved_view_store_route', 'config_partial', 'config_partial_path'] as $stringKey) {
            if (array_key_exists($stringKey, $report) && (! is_string($report[$stringKey]) || trim($report[$stringKey]) === '')) {
                $errors[] = "Field [{$stringKey}] must be a non-empty string.";
            }
        }

        $viewContents = null;

        if (isset($report['view_path']) && is_string($report['view_path'])) {
            $viewPath = base_path($report['view_path']);

            if (! file_exists($viewPath)) {
                $errors[] = "View path [{$report['view_path']}] does not exist.";
            } else {
                $viewContents = file_get_contents($viewPath) ?: '';
            }
        }

        $configContents = null;

        if (isset($report['config_partial_path']) && is_string($report['config_partial_path'])) {
            $configPartialPath = base_path($report['config_partial_path']);

            if (! file_exists($configPartialPath)) {
                $errors[] = "Config partial path [{$report['config_partial_path']}] does not exist.";
            } else {
                $configContents = file_get_contents($configPartialPath) ?: '';
            }
        }

        foreach (['index_route', 'export_route', 'saved_view_store_route'] as $routeKey) {
            if (isset($report[$routeKey]) && is_string($report[$routeKey]) && ! Route::has($report[$routeKey])) {
                $errors[] = "Route [{$report[$routeKey]}] from [{$routeKey}] does not exist.";
            }
        }

        if ($viewContents !== null) {
            if (str_contains($viewContents, "@include('reports.partials.saved-view-controls'")) {
                $errors[] = 'Report view must not render saved-view-controls directly.';
            }

            if (str_contains($viewContents, 'SavedViewControlsConfig = [')) {
                $errors[] = 'Report view must not inline saved view controls config arrays.';
            }

            if (isset($report['config_partial']) && is_string($report['config_partial'])) {
                $expectedInclude = "@include('".$report['config_partial']."')";

                if (! str_contains($viewContents, $expectedInclude)) {
                    $errors[] = "Report view must include its config partial [{$report['config_partial']}].";
                }
            }
        }

        if ($configContents !== null) {
            if (! str_contains($configContents, 'SavedViewControlsConfig = [')) {
                $errors[] = 'Config partial must define a SavedViewControlsConfig array.';
            }

            if (! str_contains($configContents, "@include('reports.partials.saved-view-controls'")) {
                $errors[] = 'Config partial must render saved-view-controls in the same Blade scope.';
            }

            foreach (["'savedViews'", "'section'", "'form'", "'hiddenFields'"] as $requiredConfigKey) {
                if (! str_contains($configContents, $requiredConfigKey)) {
                    $errors[] = "Config partial is missing config key {$requiredConfigKey}.";
                }
            }
        }

        if (! array_key_exists('hidden_fields', $report) || ! is_array($report['hidden_fields'])) {
            $errors[] = 'Field [hidden_fields] must be an array.';
        } elseif ($configContents !== null && $report['hidden_fields'] !== []) {
            foreach ($report['hidden_fields'] as $hiddenField) {
                if (! is_string($hiddenField) || trim($hiddenField) === '') {
                    $errors[] = 'Every hidden field must be a non-empty string.';
                    continue;
                }

                if (! str_contains($configContents, "'{$hiddenField}'")) {
                    $errors[] = "Config partial must contain hidden field [{$hiddenField}].";
                }
            }
        }

        if (! isset($report['test_ids']) || ! is_array($report['test_ids']) || $report['test_ids'] === []) {
            $errors[] = 'Field [test_ids] must be a non-empty array.';
        } elseif ($configContents !== null) {
            foreach ($report['test_ids'] as $testIdKey => $testId) {
                if (! is_string($testId) || trim($testId) === '') {
                    $errors[] = "Test ID [{$testIdKey}] must be a non-empty string.";
                    continue;
                }

                if (! str_contains($configContents, $testId)) {
                    $errors[] = "Config partial must contain test ID [{$testId}].";
                }
            }
        }

        return $errors;
    }
}
