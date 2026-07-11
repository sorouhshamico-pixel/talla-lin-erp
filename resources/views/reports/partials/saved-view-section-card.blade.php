<div class="card" data-testid="{{ $cardTestId ?? 'saved-view-section-card' }}">
    @include('reports.partials.saved-view-section', [
        'savedViews' => $savedViews ?? collect(),
        'routeName' => $routeName,
        'emptyTestId' => $emptyTestId,
        'listTestId' => $listTestId,
        'itemTestId' => $itemTestId,
        'openLinkTestId' => $openLinkTestId,
        'activeBadgeTestId' => $activeBadgeTestId,
        'defaultBadgeTestId' => $defaultBadgeTestId,
        'manageLinkTestId' => $manageLinkTestId,
        'emptyMessage' => $emptyMessage ?? null,
    ])
</div>
