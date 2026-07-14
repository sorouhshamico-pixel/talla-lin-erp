@php
    $reportsIndexSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'reports-index-saved-views-selector',
            'routeName' => 'reports.index',
            'emptyTestId' => 'reports-index-saved-views-empty',
            'listTestId' => 'reports-index-saved-views-list',
            'itemTestId' => 'reports-index-saved-view-item',
            'openLinkTestId' => 'reports-index-saved-view-open-link',
            'activeBadgeTestId' => 'reports-index-saved-view-active-badge',
            'defaultBadgeTestId' => 'reports-index-saved-view-default-badge',
            'manageLinkTestId' => 'reports-index-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لمركز التقارير حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'reports-index-save-view-card',
            'title' => 'حفظ عرض مركز التقارير',
            'storeRouteName' => 'reports.index.saved-views.store',
            'testId' => 'reports-index-save-view-form',
            'nameInputId' => 'reports_index_saved_view_name',
            'namePlaceholder' => 'مثال: ملخص هذا الشهر',
            'nameInputTestId' => 'reports-index-saved-view-name-input',
            'defaultCheckboxTestId' => 'reports-index-saved-view-default-checkbox',
            'saveButtonTestId' => 'reports-index-save-view-button',
        ],
        'hiddenFields' => [
            'from_date' => $filters['from_date'] ?? null,
            'to_date' => $filters['to_date'] ?? null,
            'branch_id' => $filters['branch_id'] ?? null,
            'expense_category_id' => $filters['expense_category_id'] ?? null,
            'payment_method' => $filters['payment_method'] ?? null,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $reportsIndexSavedViewControlsConfig)
