@php
    $salesInvoiceCollectionsSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'sales-invoice-collections-saved-views-selector',
            'routeName' => 'reports.sales-invoice-collections.index',
            'emptyTestId' => 'sales-invoice-collections-saved-views-empty',
            'listTestId' => 'sales-invoice-collections-saved-views-list',
            'itemTestId' => 'sales-invoice-collections-saved-view-item',
            'openLinkTestId' => 'sales-invoice-collections-saved-view-open-link',
            'activeBadgeTestId' => 'sales-invoice-collections-saved-view-active-badge',
            'defaultBadgeTestId' => 'sales-invoice-collections-saved-view-default-badge',
            'manageLinkTestId' => 'sales-invoice-collections-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لتقرير تحصيل فواتير المبيعات حتى الآن. يمكنك حفظ العرض الحالي للوصول السريع إليه لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'sales-invoice-collections-save-view-card',
            'title' => 'حفظ عرض تقرير تحصيل فواتير المبيعات',
            'storeRouteName' => 'reports.sales-invoice-collections.saved-views.store',
            'testId' => 'sales-invoice-collections-save-view-form',
            'nameInputId' => 'sales_invoice_collections_saved_view_name',
            'namePlaceholder' => 'مثال: متابعة التحصيل الحالية',
            'nameInputTestId' => 'sales-invoice-collections-saved-view-name-input',
            'defaultCheckboxTestId' => 'sales-invoice-collections-saved-view-default-checkbox',
            'saveButtonTestId' => 'sales-invoice-collections-save-view-button',
        ],
        'hiddenFields' => [],
    ];
@endphp

@include('reports.partials.saved-view-controls', $salesInvoiceCollectionsSavedViewControlsConfig)
