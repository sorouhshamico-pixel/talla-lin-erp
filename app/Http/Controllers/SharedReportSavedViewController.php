<?php

namespace App\Http\Controllers;

use App\Models\ReportSavedViewShare;
use App\Services\ReportSavedViewShareService;
use App\Support\Reports\ReportSavedViewRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SharedReportSavedViewController extends Controller
{
    public function index(
        Request $request,
        ReportSavedViewShareService $shareService
    ): View|JsonResponse {
        $shares = $shareService
            ->listReceived($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'data' => $shares
                    ->map(
                        fn (
                            ReportSavedViewShare $share
                        ): array => [
                            'id' => $share->id,
                            'permission' =>
                                $share->permission,
                            'owner' => [
                                'id' =>
                                    $share->owner?->id,
                                'name' =>
                                    $share->owner?->name,
                            ],
                            'saved_view' => [
                                'id' =>
                                    $share->savedView?->id,
                                'report_key' =>
                                    $share->savedView
                                        ?->report_key,
                                'name' =>
                                    $share->savedView?->name,
                                'archived' =>
                                    $share->savedView
                                        ?->isArchived(),
                            ],
                        ]
                    )
                    ->values(),
            ]);
        }

        return view(
            'reports.shared-saved-views.index',
            ['shares' => $shares]
        );
    }

    public function copy(
        Request $request,
        ReportSavedViewShare $share,
        ReportSavedViewShareService $shareService
    ): RedirectResponse {
        $shareService->copyToRecipient(
            $request->user(),
            $share
        );

        return redirect()
            ->route('reports.saved-views.index')
            ->with(
                'status',
                'تم نسخ العرض المشترك إلى حسابك.'
            );
    }

    public function apply(
        Request $request,
        ReportSavedViewShare $share,
        ReportSavedViewShareService $shareService
    ): RedirectResponse {
        $share = $shareService->receivedShare(
            $request->user(),
            $share,
            true
        );

        $savedView = $share->savedView;
        $routeName = ReportSavedViewRegistry::indexRoute(
            $savedView->report_key
        );

        abort_unless(
            is_string($routeName)
            && $routeName !== '',
            404
        );

        return redirect()->route(
            $routeName,
            $savedView->filters ?? []
        );
    }
}
