@php
    $savedViewCollection = $savedViews ?? collect();
@endphp

<h2>العروض المحفوظة</h2>

@include('reports.partials.saved-view-list-styles')
@include('reports.partials.saved-view-help-text')
@include('reports.partials.active-saved-view-banner', ['savedViews' => $savedViewCollection])

@include('reports.partials.saved-view-list', [
    'savedViews' => $savedViewCollection,
    'routeName' => $routeName,
    'emptyTestId' => $emptyTestId,
    'listTestId' => $listTestId,
    'itemTestId' => $itemTestId,
    'openLinkTestId' => $openLinkTestId,
    'activeBadgeTestId' => $activeBadgeTestId,
    'defaultBadgeTestId' => $defaultBadgeTestId,
    'emptyMessage' => $emptyMessage ?? null,
])

<div style="margin-top:12px;">
    <a href="{{ route('reports.saved-views.index') }}" data-testid="{{ $manageLinkTestId }}">
        إدارة العروض المحفوظة
    </a>
</div>
