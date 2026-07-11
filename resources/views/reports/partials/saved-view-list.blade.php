@if ($savedViews->isEmpty())
    @include('reports.partials.saved-view-empty-state', [
        'testId' => $emptyTestId,
        'message' => $emptyMessage ?? null,
    ])
@else
    <div class="saved-views-list" data-testid="{{ $listTestId }}">
        @foreach ($savedViews as $savedView)
            @php
                $savedViewFilters = array_filter(
                    $savedView->filters ?? [],
                    fn ($value) => $value !== null && $value !== ''
                );

                $savedViewRouteFilters = array_merge($savedViewFilters, [
                    'saved_view_id' => $savedView->id,
                ]);

                $isActiveSavedView = (int) request('saved_view_id') === (int) $savedView->id;
            @endphp

            <div class="saved-view-row{{ $isActiveSavedView ? ' active-saved-view-row' : '' }}" data-testid="{{ $itemTestId }}">
                <a href="{{ route($routeName, $savedViewRouteFilters) }}"
                   class="saved-view-link"
                   data-testid="{{ $openLinkTestId }}">
                    {{ $savedView->name }}
                </a>

                <span class="saved-view-badges">
                    @if ($isActiveSavedView)
                        <span class="saved-view-badge saved-view-badge-active" data-testid="{{ $activeBadgeTestId }}">نشط</span>
                    @endif

                    @if ($savedView->is_default)
                        <span class="saved-view-badge saved-view-badge-default" data-testid="{{ $defaultBadgeTestId }}">افتراضي</span>
                    @endif
                </span>
            </div>
        @endforeach
    </div>
@endif
