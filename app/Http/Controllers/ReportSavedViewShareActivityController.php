<?php

namespace App\Http\Controllers;

use App\Models\ReportSavedViewShareActivity;
use App\Models\User;
use App\Services\ReportSavedViewShareActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportSavedViewShareActivityController extends Controller
{
    public function ownerIndex(
        Request $request,
        ReportSavedViewShareActivityService $service
    ): View|JsonResponse {
        $activities = $service->paginateForOwner(
            $request->user(),
            $request->string('action')->toString(),
            $request->integer('recipient_user_id') ?: null,
            $request->integer('report_saved_view_id') ?: null,
            $request->string('date_from')->toString(),
            $request->string('date_to')->toString(),
            $request->integer('per_page') ?: 25
        );

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $activities->items(),
                'meta' => [
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                ],
            ]);
        }

        $recipients = User::query()
            ->whereIn(
                'id',
                ReportSavedViewShareActivity::query()
                    ->where(
                        'owner_user_id',
                        $request->user()->id
                    )
                    ->whereNotNull('recipient_user_id')
                    ->select('recipient_user_id')
            )
            ->orderBy('name')
            ->get(['id', 'name']);

        return view(
            'reports.saved-views.share-activities',
            [
                'activities' => $activities,
                'actions' =>
                    ReportSavedViewShareActivity::ACTIONS,
                'recipients' => $recipients,
            ]
        );
    }

    public function recipientIndex(
        Request $request,
        ReportSavedViewShareActivityService $service
    ): View|JsonResponse {
        $activities = $service->paginateForRecipient(
            $request->user(),
            $request->string('action')->toString(),
            $request->integer('report_saved_view_id') ?: null,
            $request->string('date_from')->toString(),
            $request->string('date_to')->toString(),
            $request->integer('per_page') ?: 25
        );

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $activities->items(),
                'meta' => [
                    'current_page' => $activities->currentPage(),
                    'last_page' => $activities->lastPage(),
                    'per_page' => $activities->perPage(),
                    'total' => $activities->total(),
                ],
            ]);
        }

        return view(
            'reports.shared-saved-views.activities',
            [
                'activities' => $activities,
                'actions' =>
                    ReportSavedViewShareActivity::ACTIONS,
            ]
        );
    }
}
