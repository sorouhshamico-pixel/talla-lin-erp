@php
    $activeSavedViewId = (int) request('saved_view_id');
    $activeSavedView = $activeSavedViewId > 0
        ? collect($savedViews ?? [])->first(fn ($savedView) => (int) $savedView->id === $activeSavedViewId)
        : null;
@endphp

@if ($activeSavedView)
    <div class="alert alert-info" data-testid="active-saved-view-banner">
        التقرير مفتوح من العرض المحفوظ:
        <strong data-testid="active-saved-view-name">{{ $activeSavedView->name }}</strong>
    </div>
@endif
