<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Support\SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth;
use Illuminate\Http\JsonResponse;

final class SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealthController
    extends Controller
{
    public function __invoke(
        SavedViewRetentionSummaryCacheDiagnosticsRefreshAuditMetricsHealth $health
    ): JsonResponse {
        return response()->json($health->status());
    }
}
