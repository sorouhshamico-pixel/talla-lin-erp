@php
    $activeSavedViewId = (int) request('saved_view_id');
    $activeSavedView = $activeSavedViewId > 0
        ? collect($savedViews ?? [])->first(fn ($savedView) => (int) $savedView->id === $activeSavedViewId)
        : null;

    $clearSavedViewQuery = request()->query();
    unset($clearSavedViewQuery['saved_view_id']);

    $clearSavedViewUrl = url()->current()
        . (empty($clearSavedViewQuery) ? '' : '?' . http_build_query($clearSavedViewQuery));
@endphp

@if ($activeSavedView)
    <div class="alert alert-info" data-testid="active-saved-view-banner">
        التقرير مفتوح من العرض المحفوظ:
        <strong data-testid="active-saved-view-name">{{ $activeSavedView->name }}</strong>

        <a href="{{ $clearSavedViewUrl }}" data-testid="active-saved-view-clear-link">
            عرض التقرير بدون العرض المحفوظ
        </a>
    </div>
@endif
