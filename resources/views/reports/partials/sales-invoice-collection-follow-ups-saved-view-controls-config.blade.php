@php
    $salesInvoiceCollectionFollowUpsSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'sales-invoice-collection-follow-ups-saved-views-selector',
            'routeName' => 'reports.sales-invoice-collection-follow-ups.index',
            'emptyTestId' => 'sales-invoice-collection-follow-ups-saved-views-empty',
            'listTestId' => 'sales-invoice-collection-follow-ups-saved-views-list',
            'itemTestId' => 'sales-invoice-collection-follow-ups-saved-view-item',
            'openLinkTestId' => 'sales-invoice-collection-follow-ups-saved-view-open-link',
            'activeBadgeTestId' => 'sales-invoice-collection-follow-ups-saved-view-active-badge',
            'defaultBadgeTestId' => 'sales-invoice-collection-follow-ups-saved-view-default-badge',
            'manageLinkTestId' => 'sales-invoice-collection-follow-ups-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لتقرير متابعات التحصيل حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'sales-invoice-collection-follow-ups-save-view-card',
            'title' => 'حفظ عرض تقرير متابعات التحصيل',
            'storeRouteName' => 'reports.sales-invoice-collection-follow-ups.saved-views.store',
            'testId' => 'sales-invoice-collection-follow-ups-save-view-form',
            'nameInputId' => 'sales_invoice_collection_follow_ups_saved_view_name',
            'namePlaceholder' => 'مثال: متابعات تحصيل هذا الأسبوع',
            'nameInputTestId' => 'sales-invoice-collection-follow-ups-saved-view-name-input',
            'defaultCheckboxTestId' => 'sales-invoice-collection-follow-ups-saved-view-default-checkbox',
            'saveButtonTestId' => 'sales-invoice-collection-follow-ups-save-view-button',
        ],
        'hiddenFields' => [
            'customer_id' => $customerFilter ?? null,
            'follow_up_from' => $followUpFromFilter ?? null,
            'follow_up_to' => $followUpToFilter ?? null,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $salesInvoiceCollectionFollowUpsSavedViewControlsConfig)
