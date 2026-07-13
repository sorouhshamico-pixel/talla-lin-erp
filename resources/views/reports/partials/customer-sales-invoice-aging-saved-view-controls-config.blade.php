@php
    $customerSalesInvoiceAgingSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'customer-aging-saved-views-selector',
            'routeName' => 'reports.customer-sales-invoice-aging.index',
            'emptyTestId' => 'customer-aging-saved-views-empty',
            'listTestId' => 'customer-aging-saved-views-list',
            'itemTestId' => 'customer-aging-saved-view-item',
            'openLinkTestId' => 'customer-aging-saved-view-open-link',
            'activeBadgeTestId' => 'customer-aging-saved-view-active-badge',
            'defaultBadgeTestId' => 'customer-aging-saved-view-default-badge',
            'manageLinkTestId' => 'customer-aging-manage-saved-views-link',
        ],
        'form' => [
            'cardTestId' => 'customer-aging-save-view-card',
            'title' => 'حفظ عرض التقرير',
            'storeRouteName' => 'reports.customer-sales-invoice-aging.saved-views.store',
            'testId' => 'customer-aging-save-view-form',
            'nameInputId' => 'customer_aging_saved_view_name',
            'namePlaceholder' => 'مثال: متابعة ذمم العملاء',
            'nameInputTestId' => 'customer-aging-saved-view-name-input',
            'defaultCheckboxTestId' => 'customer-aging-saved-view-default-checkbox',
            'saveButtonTestId' => 'customer-aging-save-view-button',
        ],
        'hiddenFields' => [
            'customer_id' => $customerFilter,
            'aging_bucket' => $agingBucketFilter,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $customerSalesInvoiceAgingSavedViewControlsConfig)
