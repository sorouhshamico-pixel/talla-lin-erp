<?php

use App\Support\Reports\ReportSavedViewRegistryDiagnosticReport;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('reports:saved-view-diagnostics {--json : Output the report saved view registry diagnostics as JSON}', function (): int {
    $payload = ReportSavedViewRegistryDiagnosticReport::build();

    if ($this->option('json')) {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $payload['summary']['valid'] ? 0 : 1;
    }

    $this->line(ReportSavedViewRegistryDiagnosticReport::markdown());

    return $payload['summary']['valid'] ? 0 : 1;
})->purpose('Show report saved view registry diagnostics');
