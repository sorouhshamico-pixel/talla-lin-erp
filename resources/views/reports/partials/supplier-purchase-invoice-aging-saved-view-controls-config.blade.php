@php
    $supplierPurchaseInvoiceAgingSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'supplier-aging-saved-views-selector',
            'routeName' => 'reports.supplier-purchase-invoice-aging.index',
            'emptyTestId' => 'supplier-aging-saved-views-empty',
            'listTestId' => 'supplier-aging-saved-views-list',
            'itemTestId' => 'supplier-aging-saved-view-item',
            'openLinkTestId' => 'supplier-aging-saved-view-open-link',
            'activeBadgeTestId' => 'supplier-aging-saved-view-active-badge',
            'defaultBadgeTestId' => 'supplier-aging-saved-view-default-badge',
            'manageLinkTestId' => 'supplier-aging-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لهذا التقرير حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'supplier-aging-save-view-card',
            'title' => 'حفظ عرض التقرير',
            'storeRouteName' => 'reports.supplier-purchase-invoice-aging.saved-views.store',
            'testId' => 'supplier-aging-save-view-form',
            'nameInputId' => 'supplier_aging_saved_view_name',
            'namePlaceholder' => 'مثال: متابعة ذمم الموردين',
            'nameInputTestId' => 'supplier-aging-saved-view-name-input',
            'defaultCheckboxTestId' => 'supplier-aging-saved-view-default-checkbox',
            'saveButtonTestId' => 'supplier-aging-save-view-button',
        ],
        'hiddenFields' => [
            'supplier_id' => request('supplier_id'),
            'aging_bucket' => request('aging_bucket'),
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $supplierPurchaseInvoiceAgingSavedViewControlsConfig)
