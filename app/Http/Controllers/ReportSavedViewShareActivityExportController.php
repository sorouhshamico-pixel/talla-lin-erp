<?php

namespace App\Http\Controllers;

use App\Services\ReportSavedViewShareActivityExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportSavedViewShareActivityExportController
    extends Controller
{
    public function owner(
        Request $request,
        ReportSavedViewShareActivityExportService $service
    ): StreamedResponse {
        return $service->ownerCsv(
            $request->user(),
            $request->only([
                'action',
                'recipient_user_id',
                'report_saved_view_id',
                'date_from',
                'date_to',
            ])
        );
    }

    public function recipient(
        Request $request,
        ReportSavedViewShareActivityExportService $service
    ): StreamedResponse {
        return $service->recipientCsv(
            $request->user(),
            $request->only([
                'action',
                'report_saved_view_id',
                'date_from',
                'date_to',
            ])
        );
    }
}
