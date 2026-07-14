@php
    $profitLossSavedViewControlsConfig = [
        'savedViews' => $savedViews ?? collect(),
        'section' => [
            'cardTestId' => 'profit-loss-saved-views-selector',
            'routeName' => 'reports.profit-loss',
            'emptyTestId' => 'profit-loss-saved-views-empty',
            'listTestId' => 'profit-loss-saved-views-list',
            'itemTestId' => 'profit-loss-saved-view-item',
            'openLinkTestId' => 'profit-loss-saved-view-open-link',
            'activeBadgeTestId' => 'profit-loss-saved-view-active-badge',
            'defaultBadgeTestId' => 'profit-loss-saved-view-default-badge',
            'manageLinkTestId' => 'profit-loss-manage-saved-views-link',
            'emptyMessage' => 'لا توجد عروض محفوظة لتقرير الأرباح والخسائر حتى الآن. اضبط الفلاتر ثم استخدم نموذج حفظ العرض لإنشاء عرض سريع الاستخدام لاحقًا.',
        ],
        'form' => [
            'cardTestId' => 'profit-loss-save-view-card',
            'title' => 'حفظ عرض تقرير الأرباح والخسائر',
            'storeRouteName' => 'reports.profit-loss.saved-views.store',
            'testId' => 'profit-loss-save-view-form',
            'nameInputId' => 'profit_loss_saved_view_name',
            'namePlaceholder' => 'مثال: أرباح وخسائر الشهر الحالي',
            'nameInputTestId' => 'profit-loss-saved-view-name-input',
            'defaultCheckboxTestId' => 'profit-loss-saved-view-default-checkbox',
            'saveButtonTestId' => 'profit-loss-save-view-button',
        ],
        'hiddenFields' => [
            'from_date' => $filters['from_date'] ?? null,
            'to_date' => $filters['to_date'] ?? null,
            'branch_id' => $filters['branch_id'] ?? null,
        ],
    ];
@endphp

@include('reports.partials.saved-view-controls', $profitLossSavedViewControlsConfig)
