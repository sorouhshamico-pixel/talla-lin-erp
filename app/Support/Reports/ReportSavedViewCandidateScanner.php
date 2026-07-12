<?php

namespace App\Support\Reports;

use Illuminate\Support\Str;

class ReportSavedViewCandidateScanner
{
    public const REPORTS_VIEW_DIRECTORY = 'resources/views/reports';

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function candidates(): array
    {
        $directory = base_path(self::REPORTS_VIEW_DIRECTORY);

        if (! is_dir($directory)) {
            return [];
        }

        $files = glob($directory.'/*.blade.php') ?: [];

        $candidates = array_values(array_filter(array_map(
            fn (string $file): ?array => self::candidateFromFile($file),
            $files
        )));

        usort($candidates, function (array $left, array $right): int {
            return [$left['registered'] ? 1 : 0, $left['key']]
                <=> [$right['registered'] ? 1 : 0, $right['key']];
        });

        return $candidates;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function unregisteredCandidates(): array
    {
        return array_values(array_filter(
            self::candidates(),
            fn (array $candidate): bool => ! $candidate['registered']
        ));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function registeredCandidates(): array
    {
        return array_values(array_filter(
            self::candidates(),
            fn (array $candidate): bool => $candidate['registered']
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public static function summary(): array
    {
        $candidates = self::candidates();
        $registered = self::registeredCandidates();
        $unregistered = self::unregisteredCandidates();

        return [
            'candidate_count' => count($candidates),
            'registered_count' => count($registered),
            'unregistered_count' => count($unregistered),
            'registered_keys' => array_column($registered, 'key'),
            'unregistered_keys' => array_column($unregistered, 'key'),
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function markdownLines(): array
    {
        $summary = self::summary();

        $lines = [
            '# Report Saved View Candidate Scanner',
            '',
            '## Summary',
            '',
            '- Candidate count: '.$summary['candidate_count'],
            '- Registered count: '.$summary['registered_count'],
            '- Unregistered count: '.$summary['unregistered_count'],
            '',
            '## Candidates',
            '',
        ];

        foreach (self::candidates() as $candidate) {
            $lines[] = '### '.$candidate['key'];
            $lines[] = '';
            $lines[] = '- View path: '.$candidate['view_path'];
            $lines[] = '- Registered: '.($candidate['registered'] ? 'yes' : 'no');
            $lines[] = '- Has GET form: '.($candidate['has_get_form'] ? 'yes' : 'no');
            $lines[] = '- Has filters: '.($candidate['has_filter_terms'] ? 'yes' : 'no');
            $lines[] = '- Has saved view controls: '.($candidate['has_saved_view_controls'] ? 'yes' : 'no');
            $lines[] = '- Priority score: '.$candidate['priority_score'];
            $lines[] = '';
        }

        return $lines;
    }

    public static function markdown(): string
    {
        return implode(PHP_EOL, self::markdownLines()).PHP_EOL;
    }

    private static function candidateFromFile(string $file): ?array
    {
        $name = basename($file);

        if (str_starts_with($name, '_')) {
            return null;
        }

        if ($name === 'saved-view-diagnostics.blade.php') {
            return null;
        }

        $relativePath = str_replace('\\', '/', Str::after($file, base_path().DIRECTORY_SEPARATOR));

        if (str_contains($relativePath, '/partials/')) {
            return null;
        }

        $key = str_replace('.blade.php', '', $name);
        $contents = file_get_contents($file) ?: '';

        $hasGetForm = self::containsGetForm($contents);
        $hasFilterTerms = self::containsFilterTerms($contents);
        $hasSavedViewControls = str_contains($contents, 'saved-view-controls')
            || str_contains($contents, 'saved_view')
            || str_contains($contents, 'savedView');

        return [
            'key' => $key,
            'view_path' => $relativePath,
            'registered' => ReportSavedViewRegistry::has($key),
            'has_get_form' => $hasGetForm,
            'has_filter_terms' => $hasFilterTerms,
            'has_saved_view_controls' => $hasSavedViewControls,
            'priority_score' => self::priorityScore($hasGetForm, $hasFilterTerms, $hasSavedViewControls, ReportSavedViewRegistry::has($key)),
        ];
    }

    private static function containsGetForm(string $contents): bool
    {
        return preg_match('/method=["\']GET["\']/i', $contents) === 1
            || preg_match('/method=["\']get["\']/i', $contents) === 1
            || str_contains($contents, '@method(\'GET\')')
            || str_contains($contents, '@method("GET")');
    }

    private static function containsFilterTerms(string $contents): bool
    {
        $terms = [
            'filter',
            'filters',
            'date_from',
            'date_to',
            'from_date',
            'to_date',
            'customer_id',
            'supplier_id',
            'status',
            'branch_id',
            'payment_status',
        ];

        foreach ($terms as $term) {
            if (str_contains($contents, $term)) {
                return true;
            }
        }

        return false;
    }

    private static function priorityScore(bool $hasGetForm, bool $hasFilterTerms, bool $hasSavedViewControls, bool $registered): int
    {
        $score = 0;

        if ($hasGetForm) {
            $score += 40;
        }

        if ($hasFilterTerms) {
            $score += 30;
        }

        if ($hasSavedViewControls) {
            $score += 20;
        }

        if (! $registered) {
            $score += 10;
        }

        return $score;
    }
}
