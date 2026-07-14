@php
    $cashFlowDashboardSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'cash-flow-dashboard-saved-views-selector',
            'routeName' => 'reports.cash-flow-dashboard.index',
            'emptyTestId' => 'cash-flow-dashboard-saved-views-empty',
            'listTestId' => 'cash-flow-dashboard-saved-views-list',
            'itemTestId' => 'cash-flow-dashboard-saved-view-item',
            'openLinkTestId' => 'cash-flow-dashboard-saved-view-open-link',
            'activeBadgeTestId' => 'cash-flow-dashboard-saved-view-active-badge',
            'defaultBadgeTestId' => 'cash-flow-dashboard-saved-view-default-badge',
            'manageLinkTestId' => 'cash-flow-dashboard-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لهذه اللوحة حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'cash-flow-dashboard-save-view-card',
            'title' => 'حفظ عرض اللوحة',
            'storeRouteName' => 'reports.cash-flow-dashboard.saved-views.store',
            'testId' => 'cash-flow-dashboard-save-view-form',
            'nameInputId' => 'cash_flow_dashboard_saved_view_name',
            'namePlaceholder' => 'مثال: تدفق الشهر الحالي',
            'nameInputTestId' => 'cash-flow-dashboard-saved-view-name-input',
            'defaultCheckboxTestId' => 'cash-flow-dashboard-saved-view-default-checkbox',
            'saveButtonTestId' => 'cash-flow-dashboard-save-view-button',
        ],
        'hiddenFields' => [
            'branch_id' => $selectedBranchId,
            'date_from' => $selectedDateFrom,
            'date_to' => $selectedDateTo,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $cashFlowDashboardSavedViewControlsConfig)
