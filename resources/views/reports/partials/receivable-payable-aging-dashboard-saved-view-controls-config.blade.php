@php
    $receivablePayableAgingDashboardSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'receivable-payable-aging-dashboard-saved-views-selector',
            'routeName' => 'reports.receivable-payable-aging-dashboard.index',
            'emptyTestId' => 'receivable-payable-aging-dashboard-saved-views-empty',
            'listTestId' => 'receivable-payable-aging-dashboard-saved-views-list',
            'itemTestId' => 'receivable-payable-aging-dashboard-saved-view-item',
            'openLinkTestId' => 'receivable-payable-aging-dashboard-saved-view-open-link',
            'activeBadgeTestId' => 'receivable-payable-aging-dashboard-saved-view-active-badge',
            'defaultBadgeTestId' => 'receivable-payable-aging-dashboard-saved-view-default-badge',
            'manageLinkTestId' => 'receivable-payable-aging-dashboard-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة للوحة أعمار الذمم حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'receivable-payable-aging-dashboard-save-view-card',
            'title' => 'حفظ عرض لوحة أعمار الذمم',
            'storeRouteName' => 'reports.receivable-payable-aging-dashboard.saved-views.store',
            'testId' => 'receivable-payable-aging-dashboard-save-view-form',
            'nameInputId' => 'receivable_payable_aging_dashboard_saved_view_name',
            'namePlaceholder' => 'مثال: أعمار الذمم نهاية الشهر',
            'nameInputTestId' => 'receivable-payable-aging-dashboard-saved-view-name-input',
            'defaultCheckboxTestId' => 'receivable-payable-aging-dashboard-saved-view-default-checkbox',
            'saveButtonTestId' => 'receivable-payable-aging-dashboard-save-view-button',
        ],
        'hiddenFields' => [
            'branch_id' => $selectedBranchId ?? null,
            'as_of_date' => $selectedAsOfDate ?? null,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $receivablePayableAgingDashboardSavedViewControlsConfig)
