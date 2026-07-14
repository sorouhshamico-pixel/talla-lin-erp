@php
    $supplierPurchaseInvoiceAgingDrilldownSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'supplier-aging-drilldown-saved-views-selector',
            'routeName' => 'reports.supplier-purchase-invoice-aging.drilldown',
            'emptyTestId' => 'supplier-aging-drilldown-saved-views-empty',
            'listTestId' => 'supplier-aging-drilldown-saved-views-list',
            'itemTestId' => 'supplier-aging-drilldown-saved-view-item',
            'openLinkTestId' => 'supplier-aging-drilldown-saved-view-open-link',
            'activeBadgeTestId' => 'supplier-aging-drilldown-saved-view-active-badge',
            'defaultBadgeTestId' => 'supplier-aging-drilldown-saved-view-default-badge',
            'manageLinkTestId' => 'supplier-aging-drilldown-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لهذه التفاصيل حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'supplier-aging-drilldown-save-view-card',
            'title' => 'حفظ عرض التفاصيل',
            'storeRouteName' => 'reports.supplier-purchase-invoice-aging.drilldown.saved-views.store',
            'testId' => 'supplier-aging-drilldown-save-view-form',
            'nameInputId' => 'supplier-aging-drilldown-save-view-form_name',
            'namePlaceholder' => 'مثال: تفاصيل موردين نهاية الشهر',
            'nameInputTestId' => 'supplier-aging-drilldown-saved-view-name-input',
            'defaultCheckboxTestId' => 'supplier-aging-drilldown-saved-view-default-checkbox',
            'saveButtonTestId' => 'supplier-aging-drilldown-save-view-button',
        ],
        'hiddenFields' => [
            'supplier_id' => $selectedSupplierId,
            'branch_id' => $selectedBranchId,
            'as_of_date' => $selectedAsOfDate,
            'aging_bucket' => $selectedAgingBucket,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $supplierPurchaseInvoiceAgingDrilldownSavedViewControlsConfig)
