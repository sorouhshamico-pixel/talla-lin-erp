<?php

namespace App\Support\Reports;

class ReportSavedViewRolloutSelector
{
    public static function prioritizedCandidates(): array
    {
        $candidates = ReportSavedViewCandidateScanner::unregisteredCandidates();

        usort($candidates, function (array $left, array $right): int {
            return [$right['priority_score'], $left['key']]
                <=> [$left['priority_score'], $right['key']];
        });

        return $candidates;
    }

    public static function nextCandidate(): ?array
    {
        return self::prioritizedCandidates()[0] ?? null;
    }

    public static function plan(): array
    {
        $nextCandidate = self::nextCandidate();
        $prioritizedCandidates = self::prioritizedCandidates();

        return [
            'has_next_candidate' => $nextCandidate !== null,
            'next_candidate' => $nextCandidate,
            'candidate_count' => count(ReportSavedViewCandidateScanner::candidates()),
            'unregistered_candidate_count' => count($prioritizedCandidates),
            'registered_candidate_count' => count(ReportSavedViewCandidateScanner::registeredCandidates()),
            'prioritized_candidates' => $prioritizedCandidates,
            'recommended_steps' => self::recommendedSteps($nextCandidate),
        ];
    }

    public static function recommendedSteps(?array $candidate = null): array
    {
        if ($candidate === null) {
            return [
                'No unregistered report saved view candidates were found.',
                'Keep the registry diagnostics healthy before adding more reports.',
            ];
        }

        return [
            'Review the Blade view: '.$candidate['view_path'],
            'Identify GET filters and hidden fields required for saved views.',
            'Create a report-specific saved view controls config partial.',
            'Add the report to ReportSavedViewRegistry.',
            'Add render, registry, and rollout guard tests.',
            'Run php artisan reports:saved-view-diagnostics after rollout.',
        ];
    }

    public static function markdownLines(): array
    {
        $plan = self::plan();
        $nextCandidate = $plan['next_candidate'];

        $lines = [
            '# Report Saved View Rollout Selector',
            '',
            '## Summary',
            '',
            '- Candidate count: '.$plan['candidate_count'],
            '- Registered candidate count: '.$plan['registered_candidate_count'],
            '- Unregistered candidate count: '.$plan['unregistered_candidate_count'],
            '- Has next candidate: '.($plan['has_next_candidate'] ? 'yes' : 'no'),
            '',
            '## Next Candidate',
            '',
        ];

        if ($nextCandidate === null) {
            $lines[] = 'No unregistered candidate found.';
            $lines[] = '';
        } else {
            $lines[] = '- Key: '.$nextCandidate['key'];
            $lines[] = '- View path: '.$nextCandidate['view_path'];
            $lines[] = '- Priority score: '.$nextCandidate['priority_score'];
            $lines[] = '- Has GET form: '.($nextCandidate['has_get_form'] ? 'yes' : 'no');
            $lines[] = '- Has filters: '.($nextCandidate['has_filter_terms'] ? 'yes' : 'no');
            $lines[] = '';
        }

        $lines[] = '## Recommended Steps';
        $lines[] = '';

        foreach ($plan['recommended_steps'] as $step) {
            $lines[] = '- '.$step;
        }

        $lines[] = '';
        $lines[] = '## Prioritized Candidates';
        $lines[] = '';

        foreach ($plan['prioritized_candidates'] as $candidate) {
            $lines[] = '### '.$candidate['key'];
            $lines[] = '';
            $lines[] = '- View path: '.$candidate['view_path'];
            $lines[] = '- Priority score: '.$candidate['priority_score'];
            $lines[] = '';
        }

        return $lines;
    }

    public static function markdown(): string
    {
        return implode(PHP_EOL, self::markdownLines()).PHP_EOL;
    }
}
