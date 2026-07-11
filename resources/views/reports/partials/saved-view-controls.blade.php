@php
    $savedViewControlsCollection = $savedViews ?? collect();
    $sectionConfig = $section ?? [];
    $formConfig = $form ?? [];
@endphp

@include('reports.partials.saved-view-section-card', [
    'cardTestId' => $sectionConfig['cardTestId'] ?? 'saved-view-section-card',
    'savedViews' => $savedViewControlsCollection,
    'routeName' => $sectionConfig['routeName'],
    'emptyTestId' => $sectionConfig['emptyTestId'],
    'listTestId' => $sectionConfig['listTestId'],
    'itemTestId' => $sectionConfig['itemTestId'],
    'openLinkTestId' => $sectionConfig['openLinkTestId'],
    'activeBadgeTestId' => $sectionConfig['activeBadgeTestId'],
    'defaultBadgeTestId' => $sectionConfig['defaultBadgeTestId'],
    'manageLinkTestId' => $sectionConfig['manageLinkTestId'],
    'emptyMessage' => $sectionConfig['emptyMessage'] ?? null,
])

@include('reports.partials.saved-view-form-card', [
    'cardTestId' => $formConfig['cardTestId'] ?? 'saved-view-form-card',
    'title' => $formConfig['title'] ?? 'حفظ عرض التقرير',
    'storeRouteName' => $formConfig['storeRouteName'],
    'formTestId' => $formConfig['testId'] ?? 'saved-view-form',
    'hiddenFields' => $hiddenFields ?? [],
    'nameInputId' => $formConfig['nameInputId'] ?? null,
    'namePlaceholder' => $formConfig['namePlaceholder'] ?? null,
    'nameInputTestId' => $formConfig['nameInputTestId'] ?? null,
    'defaultCheckboxTestId' => $formConfig['defaultCheckboxTestId'] ?? null,
    'saveButtonTestId' => $formConfig['saveButtonTestId'] ?? null,
])
