@php
    $savedViewControlsCollection = $savedViews ?? collect();

    $sectionConfig = array_replace([
        'cardTestId' => 'saved-view-section-card',
        'routeName' => null,
        'emptyTestId' => 'saved-view-empty',
        'listTestId' => 'saved-view-list',
        'itemTestId' => 'saved-view-item',
        'openLinkTestId' => 'saved-view-open-link',
        'activeBadgeTestId' => 'saved-view-active-badge',
        'defaultBadgeTestId' => 'saved-view-default-badge',
        'manageLinkTestId' => 'saved-view-manage-link',
        'emptyMessage' => null,
    ], $section ?? []);

    $formConfig = array_replace([
        'cardTestId' => 'saved-view-form-card',
        'title' => 'حفظ عرض التقرير',
        'storeRouteName' => null,
        'testId' => 'saved-view-form',
        'nameInputId' => null,
        'namePlaceholder' => null,
        'nameInputTestId' => null,
        'defaultCheckboxTestId' => null,
        'saveButtonTestId' => null,
    ], $form ?? []);
@endphp

@include('reports.partials.saved-view-section-card', [
    'cardTestId' => $sectionConfig['cardTestId'],
    'savedViews' => $savedViewControlsCollection,
    'routeName' => $sectionConfig['routeName'],
    'emptyTestId' => $sectionConfig['emptyTestId'],
    'listTestId' => $sectionConfig['listTestId'],
    'itemTestId' => $sectionConfig['itemTestId'],
    'openLinkTestId' => $sectionConfig['openLinkTestId'],
    'activeBadgeTestId' => $sectionConfig['activeBadgeTestId'],
    'defaultBadgeTestId' => $sectionConfig['defaultBadgeTestId'],
    'manageLinkTestId' => $sectionConfig['manageLinkTestId'],
    'emptyMessage' => $sectionConfig['emptyMessage'],
])

@include('reports.partials.saved-view-form-card', [
    'cardTestId' => $formConfig['cardTestId'],
    'title' => $formConfig['title'],
    'storeRouteName' => $formConfig['storeRouteName'],
    'formTestId' => $formConfig['testId'],
    'hiddenFields' => $hiddenFields ?? [],
    'nameInputId' => $formConfig['nameInputId'],
    'namePlaceholder' => $formConfig['namePlaceholder'],
    'nameInputTestId' => $formConfig['nameInputTestId'],
    'defaultCheckboxTestId' => $formConfig['defaultCheckboxTestId'],
    'saveButtonTestId' => $formConfig['saveButtonTestId'],
])
