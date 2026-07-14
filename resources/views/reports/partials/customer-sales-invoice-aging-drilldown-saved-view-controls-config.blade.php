@php
    $customerSalesInvoiceAgingDrilldownSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'customer-aging-drilldown-saved-views-selector',
            'routeName' => 'reports.customer-sales-invoice-aging.drilldown',
            'emptyTestId' => 'customer-aging-drilldown-saved-views-empty',
            'listTestId' => 'customer-aging-drilldown-saved-views-list',
            'itemTestId' => 'customer-aging-drilldown-saved-view-row',
            'openLinkTestId' => 'customer-aging-drilldown-saved-view-open-link',
            'activeBadgeTestId' => 'customer-aging-drilldown-saved-view-active-badge',
            'defaultBadgeTestId' => 'customer-aging-drilldown-saved-view-default-badge',
            'manageLinkTestId' => 'customer-aging-drilldown-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لهذه التفاصيل حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'customer-aging-drilldown-save-view-card',
            'title' => 'حفظ عرض التفاصيل',
            'storeRouteName' => 'reports.customer-sales-invoice-aging.drilldown.saved-views.store',
            'testId' => 'customer-aging-drilldown-save-view-form',
            'nameInputId' => 'customer_aging_drilldown_saved_view_name',
            'namePlaceholder' => 'مثال: تفاصيل عملاء نهاية الشهر',
            'nameInputTestId' => 'customer-aging-drilldown-saved-view-name-input',
            'defaultCheckboxTestId' => 'customer-aging-drilldown-saved-view-default-checkbox',
            'saveButtonTestId' => 'customer-aging-drilldown-save-view-button',
        ],
        'hiddenFields' => [
            'customer_id' => $selectedCustomerId,
            'branch_id' => $selectedBranchId,
            'as_of_date' => $selectedAsOfDate,
            'aging_bucket' => $selectedAgingBucket,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $customerSalesInvoiceAgingDrilldownSavedViewControlsConfig)
