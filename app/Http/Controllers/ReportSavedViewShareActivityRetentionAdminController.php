<?php

namespace App\Http\Controllers;

use App\Services\ReportSavedViewShareActivityRetentionAdminService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryExportService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use App\Services\ReportSavedViewShareActivityRetentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class ReportSavedViewShareActivityRetentionAdminController extends Controller
{
    private const DIAGNOSTICS_REFRESH_SUCCEEDED_EVENT =
        'saved_view_retention.summary_cache_diagnostics.refresh_succeeded';

    private const DIAGNOSTICS_REFRESH_FAILED_EVENT =
        'saved_view_retention.summary_cache_diagnostics.refresh_failed';

    public function index(
        Request $request,
        ReportSavedViewShareActivityRetentionAdminService $service,
        ReportSavedViewShareActivityRetentionExecutionHistoryExportService $export
    ): View|JsonResponse {
        $status = $service->status();

        if ($request->expectsJson()) {
            return response()->json($status);
        }

        $filters = $export->validatedFilters(
            $request->only([
                'type',
                'status',
                'actor_user_id',
                'started_from',
                'started_to',
            ])
        );

        return view(
            'reports.saved-views.share-activity-retention',
            [
                'status' => $status,
                'exportFilters' => $filters,
                'exportSummary' => $export->summary($filters),
                'exportSummaryCacheDiagnostics' =>
                    $export->summaryCacheDiagnostics(),
            ]
        );
    }

    public function summaryCacheDiagnostics(
        ReportSavedViewShareActivityRetentionExecutionHistoryExportService $export
    ): JsonResponse {
        try {
            $diagnostics = $export->summaryCacheDiagnostics();
        } catch (Throwable $exception) {
            $this->observe(
                'warning',
                self::DIAGNOSTICS_REFRESH_FAILED_EVENT,
                [
                    'failure_reason_class' => $exception::class,
                ]
            );

            throw $exception;
        }

        $this->observe(
            'debug',
            self::DIAGNOSTICS_REFRESH_SUCCEEDED_EVENT,
            [
                'cache_store' => $diagnostics['cache_store'],
                'cache_read_available' =>
                    $diagnostics['cache_read_available'],
                'generation_present' =>
                    $diagnostics['generation_present'],
                'generation_source' =>
                    $diagnostics['generation_source'],
                'observability_enabled' =>
                    $diagnostics['observability_enabled'],
            ]
        );

        return response()->json($diagnostics);
    }

    public function preview(
        Request $request,
        ReportSavedViewShareActivityRetentionAdminService $service,
        ReportSavedViewShareActivityRetentionService $retention,
        ReportSavedViewShareActivityRetentionExecutionHistoryService $history
    ): JsonResponse {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:30', 'max:3650'],
        ]);

        return response()->json($service->preview(
            $request->user(),
            (int) $validated['days'],
            $retention,
            $history
        ));
    }

    public function execute(
        Request $request,
        ReportSavedViewShareActivityRetentionAdminService $service,
        ReportSavedViewShareActivityRetentionService $retention,
        ReportSavedViewShareActivityRetentionExecutionHistoryService $history
    ): JsonResponse {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:30', 'max:3650'],
            'chunk_size' => ['required', 'integer', 'min:1', 'max:10000'],
            'confirmation' => ['required', 'in:PRUNE'],
        ]);

        try {
            $result = $service->execute(
                $request->user(),
                (int) $validated['days'],
                (int) $validated['chunk_size'],
                $retention,
                $history
            );
        } catch (RuntimeException $exception) {
            if ($exception->getCode() === 409) {
                return response()->json(
                    ['message' => $exception->getMessage()],
                    Response::HTTP_CONFLICT
                );
            }
            throw $exception;
        }

        return response()->json($result);
    }

    private function observe(
        string $level,
        string $event,
        array $context
    ): void {
        try {
            Log::log(
                $level,
                $event,
                array_merge(
                    ['event' => $event],
                    $context
                )
            );
        } catch (Throwable) {
        }
    }
}
