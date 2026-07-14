@php
    $financialDashboardSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'financial-dashboard-saved-views-selector',
            'routeName' => 'reports.financial-dashboard',
            'emptyTestId' => 'financial-dashboard-saved-views-empty',
            'listTestId' => 'financial-dashboard-saved-views-list',
            'itemTestId' => 'financial-dashboard-saved-view-item',
            'openLinkTestId' => 'financial-dashboard-saved-view-open-link',
            'activeBadgeTestId' => 'financial-dashboard-saved-view-active-badge',
            'defaultBadgeTestId' => 'financial-dashboard-saved-view-default-badge',
            'manageLinkTestId' => 'financial-dashboard-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة للداشبورد المالية حتى الآن. يمكنك حفظ العرض الحالي للوصول السريع إليه لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'financial-dashboard-save-view-card',
            'title' => 'حفظ عرض الداشبورد المالية',
            'storeRouteName' => 'reports.financial-dashboard.saved-views.store',
            'testId' => 'financial-dashboard-save-view-form',
            'nameInputId' => 'financial_dashboard_saved_view_name',
            'namePlaceholder' => 'مثال: الداشبورد المالية الحالية',
            'nameInputTestId' => 'financial-dashboard-saved-view-name-input',
            'defaultCheckboxTestId' => 'financial-dashboard-saved-view-default-checkbox',
            'saveButtonTestId' => 'financial-dashboard-save-view-button',
        ],
        'hiddenFields' => [],
    ];
@endphp

@include('reports.partials.saved-view-controls', $financialDashboardSavedViewControlsConfig)
