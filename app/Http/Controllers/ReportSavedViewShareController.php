<?php

namespace App\Http\Controllers;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewShare;
use App\Models\User;
use App\Services\ReportSavedViewShareService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportSavedViewShareController extends Controller
{
    public function index(
        Request $request,
        ReportSavedView $savedView,
        ReportSavedViewShareService $shareService
    ): View {
        $shares = $shareService->listRecipients(
            $request->user(),
            $savedView
        );

        $recipientOptions = User::query()
            ->whereKeyNot($request->user()->id)
            ->orderBy('name')
            ->orderBy('id')
            ->get([
                'id',
                'name',
                'email',
            ]);

        return view(
            'reports.saved-views.shares',
            [
                'savedView' => $savedView,
                'shares' => $shares,
                'recipientOptions' =>
                    $recipientOptions,
            ]
        );
    }

    public function store(
        Request $request,
        ReportSavedView $savedView,
        ReportSavedViewShareService $shareService
    ): RedirectResponse {
        $validated = $request->validate([
            'recipient_user_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'permission' => [
                'required',
                'in:view,use',
            ],
        ]);

        $recipient = User::query()->findOrFail(
            (int) $validated['recipient_user_id']
        );

        $shareService->share(
            $request->user(),
            $savedView,
            $recipient,
            (string) $validated['permission']
        );

        return back()->with(
            'success',
            'تمت مشاركة العرض المحفوظ.'
        );
    }

    public function update(
        Request $request,
        ReportSavedViewShare $share,
        ReportSavedViewShareService $shareService
    ): RedirectResponse {
        $validated = $request->validate([
            'permission' => [
                'required',
                'in:view,use',
            ],
        ]);

        $shareService->updatePermission(
            $request->user(),
            $share,
            (string) $validated['permission']
        );

        return back()->with(
            'success',
            'تم تحديث صلاحية المشاركة.'
        );
    }

    public function destroy(
        Request $request,
        ReportSavedViewShare $share,
        ReportSavedViewShareService $shareService
    ): RedirectResponse {
        $shareService->revoke(
            $request->user(),
            $share
        );

        return back()->with(
            'success',
            'تم إلغاء المشاركة.'
        );
    }
}
