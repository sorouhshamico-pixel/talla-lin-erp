<?php

use App\Support\Reports\ReportSavedViewRolloutSelector;
use App\Support\Reports\ReportSavedViewCandidateScanner;
use App\Support\Reports\ReportSavedViewDiagnosticSnapshotExporter;
use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:saved-view-diagnostics {--json : Output the report saved view registry diagnostics as JSON} {--write : Write the diagnostics snapshot to storage/app/report-saved-view-diagnostics} {--format=markdown : Snapshot format when using --write: markdown or json} {--prune : Delete generated diagnostic snapshot files} {--include-manifest : Include manifest.json when pruning diagnostic snapshots}', function (): int {
    $payload = ReportSavedViewRegistryDiagnosticReport::build();

    if ($this->option('prune')) {
        $result = ReportSavedViewDiagnosticSnapshotExporter::pruneSnapshots((bool) $this->option('include-manifest'));

        $this->line('Report saved view diagnostic snapshots pruned: '.$result['deleted_count']);
        $this->line('Manifest preserved: '.($result['manifest_preserved'] ? 'yes' : 'no'));

        return 0;
    }

    if ($this->option('write')) {
        $format = $this->option('json') ? 'json' : $this->option('format');
        $snapshot = ReportSavedViewDiagnosticSnapshotExporter::export($format);

        $this->line('Report saved view diagnostics snapshot written to: '.$snapshot['relative_path']);

        return $payload['summary']['valid'] ? 0 : 1;
    }

    if ($this->option('json')) {
        $this->line(ReportSavedViewRegistryDiagnosticReport::json());

        return $payload['summary']['valid'] ? 0 : 1;
    }

    $this->line(ReportSavedViewRegistryDiagnosticReport::markdown());

    return $payload['summary']['valid'] ? 0 : 1;
})->purpose('Show report saved view registry diagnostics');

Artisan::command('reports:saved-view-candidates {--json : Output the candidate scan as JSON}', function () {
    $payload = [
        'summary' => ReportSavedViewCandidateScanner::summary(),
        'candidates' => ReportSavedViewCandidateScanner::candidates(),
    ];

    if ($this->option('json')) {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    $this->line(ReportSavedViewCandidateScanner::markdown());

    return self::SUCCESS;
})->purpose('Scan report views and list saved view rollout candidates.');

Artisan::command('reports:saved-view-rollout-selector {--json : Output the rollout selector plan as JSON}', function () {
    $payload = ReportSavedViewRolloutSelector::plan();

    if ($this->option('json')) {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    $this->line(ReportSavedViewRolloutSelector::markdown());

    return self::SUCCESS;
})->purpose('Select the next report saved view rollout candidate.');
