@php
    $savedViewCandidatesSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'saved-view-candidates-saved-views-selector',
            'routeName' => 'reports.saved-view-candidates.index',
            'emptyTestId' => 'saved-view-candidates-saved-views-empty',
            'listTestId' => 'saved-view-candidates-saved-views-list',
            'itemTestId' => 'saved-view-candidates-saved-view-item',
            'openLinkTestId' => 'saved-view-candidates-saved-view-open-link',
            'activeBadgeTestId' => 'saved-view-candidates-saved-view-active-badge',
            'defaultBadgeTestId' => 'saved-view-candidates-saved-view-default-badge',
            'manageLinkTestId' => 'saved-view-candidates-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لصفحة مرشحي Saved Views حتى الآن. يمكنك حفظ العرض الحالي للوصول السريع إليه لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'saved-view-candidates-save-view-card',
            'title' => 'حفظ عرض مرشحي Saved Views',
            'storeRouteName' => 'reports.saved-view-candidates.saved-views.store',
            'testId' => 'saved-view-candidates-save-view-form',
            'nameInputId' => 'saved_view_candidates_saved_view_name',
            'namePlaceholder' => 'مثال: متابعة مرشحي Saved Views',
            'nameInputTestId' => 'saved-view-candidates-saved-view-name-input',
            'defaultCheckboxTestId' => 'saved-view-candidates-saved-view-default-checkbox',
            'saveButtonTestId' => 'saved-view-candidates-save-view-button',
        ],
        'hiddenFields' => [],
    ];
@endphp

@include('reports.partials.saved-view-controls', $savedViewCandidatesSavedViewControlsConfig)
