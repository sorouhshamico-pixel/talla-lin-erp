<?php

namespace App\Http\Controllers;

use App\Services\ReportSavedViewShareActivityRetentionAdminService;
use App\Services\ReportSavedViewShareActivityRetentionExecutionHistoryService;
use App\Services\ReportSavedViewShareActivityRetentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class ReportSavedViewShareActivityRetentionAdminController extends Controller
{
    public function index(Request $request, ReportSavedViewShareActivityRetentionAdminService $service): View|JsonResponse
    {
        $status = $service->status();
        return $request->expectsJson()
            ? response()->json($status)
            : view('reports.saved-views.share-activity-retention', ['status' => $status]);
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
}
