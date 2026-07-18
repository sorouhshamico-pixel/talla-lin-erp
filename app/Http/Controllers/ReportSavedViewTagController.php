<?php

namespace App\Http\Controllers;

use App\Models\ReportSavedView;
use App\Models\ReportSavedViewTag;
use App\Services\ReportSavedViewTagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReportSavedViewTagController extends Controller
{
    public function store(
        Request $request,
        ReportSavedViewTagService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:40',
            ],
            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $service->create(
            $request->user(),
            $validated['name'],
            $validated['color'] ?? null
        );

        return back()->with(
            'status',
            'تم إنشاء الوسم.'
        );
    }

    public function update(
        Request $request,
        ReportSavedViewTag $tag,
        ReportSavedViewTagService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:40',
            ],
            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
        ]);

        $service->update(
            $request->user(),
            $tag,
            $validated['name'],
            $validated['color'] ?? null
        );

        return back()->with(
            'status',
            'تم تحديث الوسم.'
        );
    }

    public function destroy(
        Request $request,
        ReportSavedViewTag $tag,
        ReportSavedViewTagService $service
    ): RedirectResponse {
        $service->delete(
            $request->user(),
            $tag
        );

        return back()->with(
            'status',
            'تم حذف الوسم.'
        );
    }

    public function sync(
        Request $request,
        ReportSavedView $savedView,
        ReportSavedViewTagService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'distinct'],
        ]);

        $service->syncSavedViewTags(
            $request->user(),
            $savedView,
            $validated['tag_ids'] ?? []
        );

        return back()->with(
            'status',
            'تم تحديث وسوم العرض.'
        );
    }

    public function bulkAttach(
        Request $request,
        ReportSavedViewTagService $service
    ): RedirectResponse {
        $validated = $request->validate(
            $this->bulkRules()
        );

        $count = $service->bulkAttach(
            $request->user(),
            $validated['saved_view_ids'],
            $validated['tag_ids']
        );

        return back()->with(
            'status',
            'تم إسناد الوسوم إلى '
                . $count
                . ' عرض.'
        );
    }

    public function bulkDetach(
        Request $request,
        ReportSavedViewTagService $service
    ): RedirectResponse {
        $validated = $request->validate(
            $this->bulkRules()
        );

        $count = $service->bulkDetach(
            $request->user(),
            $validated['saved_view_ids'],
            $validated['tag_ids']
        );

        return back()->with(
            'status',
            'تمت إزالة الوسوم من '
                . $count
                . ' عرض.'
        );
    }

    private function bulkRules(): array
    {
        return [
            'saved_view_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'saved_view_ids.*' => [
                'integer',
                'distinct',
            ],
            'tag_ids' => [
                'required',
                'array',
                'min:1',
            ],
            'tag_ids.*' => [
                'integer',
                'distinct',
            ],
        ];
    }
}
