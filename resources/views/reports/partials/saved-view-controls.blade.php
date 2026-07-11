@php
    $savedViewControlsCollection = $savedViews ?? collect();
@endphp

@include('reports.partials.saved-view-section-card', [
    'cardTestId' => $sectionCardTestId ?? 'saved-view-section-card',
    'savedViews' => $savedViewControlsCollection,
    'routeName' => $sectionRouteName,
    'emptyTestId' => $sectionEmptyTestId,
    'listTestId' => $sectionListTestId,
    'itemTestId' => $sectionItemTestId,
    'openLinkTestId' => $sectionOpenLinkTestId,
    'activeBadgeTestId' => $sectionActiveBadgeTestId,
    'defaultBadgeTestId' => $sectionDefaultBadgeTestId,
    'manageLinkTestId' => $sectionManageLinkTestId,
    'emptyMessage' => $sectionEmptyMessage ?? null,
])

@include('reports.partials.saved-view-form-card', [
    'cardTestId' => $formCardTestId ?? 'saved-view-form-card',
    'title' => $formTitle ?? 'حفظ عرض التقرير',
    'storeRouteName' => $formStoreRouteName,
    'formTestId' => $formTestId ?? 'saved-view-form',
    'hiddenFields' => $hiddenFields ?? [],
    'nameInputId' => $nameInputId ?? null,
    'namePlaceholder' => $namePlaceholder ?? null,
    'nameInputTestId' => $nameInputTestId ?? null,
    'defaultCheckboxTestId' => $defaultCheckboxTestId ?? null,
    'saveButtonTestId' => $saveButtonTestId ?? null,
])
