@php
    $salesInvoiceAgingSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'sales-invoice-aging-saved-views-selector',
            'routeName' => 'reports.sales-invoice-aging.index',
            'emptyTestId' => 'sales-invoice-aging-saved-views-empty',
            'listTestId' => 'sales-invoice-aging-saved-views-list',
            'itemTestId' => 'sales-invoice-aging-saved-view-item',
            'openLinkTestId' => 'sales-invoice-aging-saved-view-open-link',
            'activeBadgeTestId' => 'sales-invoice-aging-saved-view-active-badge',
            'defaultBadgeTestId' => 'sales-invoice-aging-saved-view-default-badge',
            'manageLinkTestId' => 'sales-invoice-aging-manage-saved-views-link',
        ],
        'form' => [
            'cardTestId' => 'sales-invoice-aging-save-view-card',
            'title' => 'حفظ عرض التقرير',
            'storeRouteName' => 'reports.sales-invoice-aging.saved-views.store',
            'testId' => 'sales-invoice-aging-save-view-form',
            'nameInputId' => 'sales_invoice_aging_saved_view_name',
            'namePlaceholder' => 'مثال: متابعة التحصيل الجزئي',
            'nameInputTestId' => 'sales-invoice-aging-saved-view-name-input',
            'defaultCheckboxTestId' => 'sales-invoice-aging-saved-view-default-checkbox',
            'saveButtonTestId' => 'sales-invoice-aging-save-view-button',
        ],
        'hiddenFields' => [
            'customer_id' => $customerFilter,
            'payment_status' => $paymentStatusFilter,
            'aging_bucket' => $agingBucketFilter,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $salesInvoiceAgingSavedViewControlsConfig)
