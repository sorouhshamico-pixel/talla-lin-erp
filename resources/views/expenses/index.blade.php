@extends('layouts.app')

@section('content')

    {{-- 13A_EXPENSE_PAGE_STATUS_BAR --}}
    @php
        $expensePageActiveFilters13A = collect(request()->query())
            ->except(['page'])
            ->filter(function ($value) {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($item) => filled($item))->isNotEmpty();
                }

                return filled($value);
            });

        $expensePageActiveFilterCount13A = $expensePageActiveFilters13A->count();
        $expensePageHasActiveFilters13A = $expensePageActiveFilterCount13A > 0;
    @endphp

    <div
        class="card"
        data-testid="expense-page-status-bar"
        data-page-active-filter-count="{{ $expensePageActiveFilterCount13A }}"
        data-page-has-active-filters="{{ $expensePageHasActiveFilters13A ? 'yes' : 'no' }}"
        style="margin-bottom:20px;border-color:#d1fae5;background:#ecfdf5;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">حالة صفحة المصروفات</h2>
                <div class="muted">
                    {{ $expensePageHasActiveFilters13A ? 'فلترة نشطة' : 'بدون فلاتر نشطة' }}
                </div>
            </div>

            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <span
                    data-testid="expense-page-status-filter-count"
                    style="display:inline-flex;gap:6px;align-items:center;padding:8px 12px;border:1px solid #a7f3d0;border-radius:999px;background:#fff;color:#065f46;"
                >
                    <strong>عدد الفلاتر:</strong>
                    <span>{{ $expensePageActiveFilterCount13A }}</span>
                </span>
            </div>
        </div>
    </div>

    {{-- 13B_EXPENSE_ACTIVE_FILTER_ALERT --}}
    @php
        $expenseActiveFiltersAlert13B = collect(request()->query())
            ->except(['page'])
            ->filter(function ($value) {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($item) => filled($item))->isNotEmpty();
                }

                return filled($value);
            });

        $expenseActiveFiltersAlertCount13B = $expenseActiveFiltersAlert13B->count();
    @endphp

    @if ($expenseActiveFiltersAlertCount13B > 0)
        <div
            class="card"
            data-testid="expense-active-filter-alert"
            data-active-filter-alert-count="{{ $expenseActiveFiltersAlertCount13B }}"
            style="margin-bottom:20px;border-color:#fde68a;background:#fffbeb;"
        >
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
                <div>
                    <h2 style="margin-top:0;">تنبيه: توجد فلاتر نشطة</h2>
                    <div class="muted">
                        النتائج المعروضة الآن لا تمثل كل المصروفات، بل تعتمد على الفلاتر المطبقة حاليًا.
                    </div>
                </div>

                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <span
                        data-testid="expense-active-filter-alert-count"
                        style="display:inline-flex;gap:6px;align-items:center;padding:8px 12px;border:1px solid #fcd34d;border-radius:999px;background:#fff;color:#92400e;"
                    >
                        <strong>الفلاتر النشطة:</strong>
                        <span>{{ $expenseActiveFiltersAlertCount13B }}</span>
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- 13C_EXPENSE_ACTIVE_FILTER_COUNT_CARD --}}
    @php
        $expenseActiveFilters13C = collect(request()->query())
            ->except(['page'])
            ->filter(function ($value) {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($item) => filled($item))->isNotEmpty();
                }

                return filled($value);
            });

        $expenseActiveFilterCount13C = $expenseActiveFilters13C->count();
    @endphp

    <div
        class="card"
        data-testid="expense-active-filter-count-card"
        data-active-filter-count="{{ $expenseActiveFilterCount13C }}"
        style="margin-bottom:20px;border-color:#dbeafe;background:#eff6ff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">عدد فلاتر المصروفات النشطة</h2>
                <div class="muted">
                    يعرض هذا العدّاد عدد فلاتر المصروفات المطبقة حاليًا باستثناء ترقيم الصفحات والقيم الفارغة.
                </div>
            </div>

            <div style="min-width:120px;text-align:center;padding:12px 16px;border:1px solid #bfdbfe;border-radius:10px;background:#fff;">
                <div class="muted">الفلاتر</div>
                <strong data-testid="expense-active-filter-count">{{ $expenseActiveFilterCount13C }}</strong>
            </div>
        </div>
    </div>

    {{-- 13D_EXPENSE_ACTIVE_FILTER_LABELS_CARD --}}
    @php
        $expenseFilterLabels13D = [
            'branch_id' => 'الفرع',
            'expense_category_id' => 'تصنيف المصروف',
            'category_id' => 'تصنيف المصروف',
            'payment_method' => 'طريقة الدفع',
            'status' => 'حالة المصروف',
            'payment_status' => 'حالة الدفع',
            'date_from' => 'من تاريخ',
            'date_to' => 'إلى تاريخ',
            'from' => 'من تاريخ',
            'to' => 'إلى تاريخ',
            'search' => 'بحث',
            'q' => 'بحث',
            'keyword' => 'بحث',
            'has_attachment' => 'حالة المرفق',
            'missing_attachment' => 'بدون مرفق',
            'archive_status' => 'حالة الأرشفة',
            'archived' => 'حالة الأرشفة',
        ];

        $expenseFilterValueLabels13D = [
            'cash' => 'نقدًا',
            'bank_transfer' => 'تحويل بنكي',
            'mada' => 'مدى',
            'visa' => 'بطاقة',
            'cheque' => 'شيك',
            'paid' => 'مدفوع',
            'unpaid' => 'غير مدفوع',
            'pending' => 'قيد الانتظار',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            'active' => 'نشط',
            'archived' => 'مؤرشف',
            'with_attachment' => 'بمرفق',
            'without_attachment' => 'بدون مرفق',
            '1' => 'نعم',
            '0' => 'لا',
            'true' => 'نعم',
            'false' => 'لا',
        ];

        $expenseActiveFilterBadges13D = collect(request()->query())
            ->except(['page'])
            ->filter(function ($value) {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($item) => filled($item))->isNotEmpty();
                }

                return filled($value);
            })
            ->map(function ($value, $key) use ($expenseFilterLabels13D, $expenseFilterValueLabels13D) {
                $displayValue = is_array($value)
                    ? collect($value)
                        ->filter(fn ($item) => filled($item))
                        ->map(fn ($item) => $expenseFilterValueLabels13D[(string) $item] ?? (string) $item)
                        ->implode(', ')
                    : (string) $value;

                if (! is_array($value)) {
                    $displayValue = $expenseFilterValueLabels13D[$displayValue] ?? $displayValue;
                }

                return [
                    'key' => $key,
                    'label' => $expenseFilterLabels13D[$key] ?? $key,
                    'value' => $displayValue,
                ];
            })
            ->values();
    @endphp

    <div
        class="card"
        data-testid="expense-active-filter-labels-card"
        style="margin-bottom:20px;border-color:#e0e7ff;background:#eef2ff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">فلاتر المصروفات النشطة الحالية</h2>
                <div class="muted">
                    يعرض هذا القسم أسماء فلاتر المصروفات المطبقة حاليًا وقيمها، مع تجاهل ترقيم الصفحات والقيم الفارغة.
                </div>
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;" data-testid="expense-active-filter-labels-list">
            @forelse ($expenseActiveFilterBadges13D as $filter)
                <span
                    data-testid="expense-active-filter-label"
                    data-filter-key="{{ $filter['key'] }}"
                    style="display:inline-flex;gap:6px;align-items:center;padding:7px 10px;border:1px solid #c7d2fe;border-radius:999px;background:#fff;color:#3730a3;font-size:13px;"
                >
                    <strong>{{ $filter['label'] }}:</strong>
                    <span>{{ $filter['value'] }}</span>
                </span>
            @empty
                <span class="muted" data-testid="expense-no-active-filter-labels">لا توجد فلاتر مصروفات نشطة حاليًا</span>
            @endforelse
        </div>
    </div>

    {{-- 13E_EXPENSE_CLEAR_ALL_FILTERS_CARD --}}
    <div
        class="card"
        data-testid="expense-clear-all-filters-card"
        style="margin-bottom:20px;border-color:#fee2e2;background:#fff7f7;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مسح كل فلاتر المصروفات</h2>
                <div class="muted">
                    استخدم هذا الزر لإزالة جميع فلاتر المصروفات الحالية والرجوع إلى قائمة المصروفات الأساسية.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index') }}"
                    class="btn secondary"
                    data-testid="expense-clear-all-filters"
                >
                    مسح كل الفلاتر
                </a>
            </div>
        </div>
    </div>

    {{-- 13I_EXPENSE_MISSING_ATTACHMENT_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-missing-attachment-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات بدون مرفق</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي تحتاج إلى مراجعة المرفقات.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['has_attachment' => '0'])) }}"
                    class="btn secondary"
                    data-testid="expense-missing-attachment-quick-filter"
                >
                    عرض المصروفات بدون مرفق
                </a>
            </div>
        </div>
    </div>

    @php
        $expenseWithAttachmentQuickFilterQuery = array_merge(request()->query(), [
            'has_attachment' => '1',
        ]);
    @endphp

    <div
        class="card"
        data-testid="expense-with-attachment-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات بها مرفق</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي تحتوي على مرفقات.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', $expenseWithAttachmentQuickFilterQuery) }}"
                    class="btn secondary"
                    data-testid="expense-with-attachment-quick-filter"
                >
                    عرض المصروفات التي بها مرفق
                </a>
            </div>
        </div>
    </div>

    @php
        $expenseMissingUnpaidQuickFilterQuery = array_merge(request()->query(), [
            'has_attachment' => '0',
            'payment_status' => 'unpaid',
        ]);
    @endphp

    <div
        class="card"
        data-testid="expense-missing-unpaid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات بدون مرفق وغير مدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي لا تحتوي على مرفق ولم يتم سدادها بعد.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', $expenseMissingUnpaidQuickFilterQuery) }}"
                    class="btn secondary"
                    data-testid="expense-missing-unpaid-quick-filter"
                >
                    عرض المصروفات بدون مرفق وغير المدفوعة
                </a>
            </div>
        </div>
    </div>

    @php
        $expenseMissingPaidQuickFilterQuery = array_merge(request()->query(), [
            'has_attachment' => '0',
            'payment_status' => 'paid',
        ]);
    @endphp

    <div
        class="card"
        data-testid="expense-missing-paid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات بدون مرفق ومدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي لا تحتوي على مرفق وتم سدادها.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', $expenseMissingPaidQuickFilterQuery) }}"
                    class="btn secondary"
                    data-testid="expense-missing-paid-quick-filter"
                >
                    عرض المصروفات بدون مرفق والمدفوعة
                </a>
            </div>
        </div>
    </div>

    {{-- 13J_EXPENSE_PAID_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-paid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات مدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي تم تسجيلها كمدفوعة.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['payment_status' => 'paid'])) }}"
                    class="btn secondary"
                    data-testid="expense-paid-quick-filter"
                >
                    عرض المصروفات المدفوعة
                </a>
            </div>
        </div>
    </div>

    {{-- 13K_EXPENSE_UNPAID_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-unpaid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات غير مدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي لم يتم تسجيلها كمدفوعة بعد.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['payment_status' => 'unpaid'])) }}"
                    class="btn secondary"
                    data-testid="expense-unpaid-quick-filter"
                >
                    عرض المصروفات غير المدفوعة
                </a>
            </div>
        </div>
    </div>

    {{-- 13M_EXPENSE_LARGE_AMOUNT_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-large-amount-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات كبيرة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي بلغت حد المصروفات الكبيرة.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['large_amount' => '1'])) }}"
                    class="btn secondary"
                    data-testid="expense-large-amount-quick-filter"
                >
                    عرض المصروفات الكبيرة
                </a>
            </div>
        </div>
    </div>

    {{-- 13N_EXPENSE_LARGE_UNPAID_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-large-unpaid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">المصاريف الكبيرة غير المدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصاريف التي تبلغ 1,000.00 ريال أو أكثر ولم يتم سدادها بعد.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['large_amount' => '1', 'payment_status' => 'unpaid'])) }}"
                    class="btn secondary"
                    data-testid="expense-large-unpaid-quick-filter"
                >
                    عرض المصروفات الكبيرة غير المدفوعة
                </a>
            </div>
        </div>
    </div>

    {{-- 13O_EXPENSE_SMALL_AMOUNT_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-small-amount-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات صغيرة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات التي لا تدخل ضمن حد المصروفات الكبيرة.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['large_amount' => '0'])) }}"
                    class="btn secondary"
                    data-testid="expense-small-amount-quick-filter"
                >
                    عرض المصروفات الصغيرة
                </a>
            </div>
        </div>
    </div>

    {{-- 13P_EXPENSE_SMALL_UNPAID_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-small-unpaid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات صغيرة غير مدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات الصغيرة التي لم يتم سدادها بعد.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['large_amount' => '0', 'payment_status' => 'unpaid'])) }}"
                    class="btn secondary"
                    data-testid="expense-small-unpaid-quick-filter"
                >
                    عرض المصروفات الصغيرة غير المدفوعة
                </a>
            </div>
        </div>
    </div>

    {{-- 13Q_EXPENSE_SMALL_PAID_QUICK_FILTER_CARD --}}
    <div
        class="card"
        data-testid="expense-small-paid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات صغيرة مدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات الصغيرة التي تم سدادها.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', array_merge(request()->query(), ['large_amount' => '0', 'payment_status' => 'paid'])) }}"
                    class="btn secondary"
                    data-testid="expense-small-paid-quick-filter"
                >
                    عرض المصروفات الصغيرة المدفوعة
                </a>
            </div>
        </div>
    </div>

    @php
        $expenseLargePaidQuickFilterQuery = array_merge(request()->query(), [
            'large_amount' => '1',
            'payment_status' => 'paid',
        ]);
    @endphp

    <div
        class="card"
        data-testid="expense-large-paid-quick-filter-card"
        data-quick-filter-card="expense"
        data-quick-filter-style="unified"
        style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">مصروفات كبيرة مدفوعة</h2>
                <div class="muted">
                    استخدم هذا الفلتر السريع لعرض المصروفات الكبيرة التي تم سدادها.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.index', $expenseLargePaidQuickFilterQuery) }}"
                    class="btn secondary"
                    data-testid="expense-large-paid-quick-filter"
                >
                    عرض المصروفات الكبيرة المدفوعة
                </a>
            </div>
        </div>
    </div>

    {{-- 13Q_EXPENSE_SMALL_PAID_QUICK_FILTER_CARD --}}
        <div class="page-header">
        <div>
            <h1 class="page-title">المصاريف التشغيلية</h1>
            <div class="muted">
                متابعة المصاريف التشغيلية مع الفلترة حسب الفترة والفرع والتصنيف وطريقة الدفع وحالة الدفع والمرفقات.
            </div>
        </div>

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a href="{{ route('expenses.export', request()->query()) }}"
               style="background:#eee4dc;color:#5d3b25;padding:12px 18px;border-radius:12px;font-weight:700;">
                تصدير CSV
            </a>

            <a href="{{ route('expenses.create') }}"
               style="background:#8b5e3c;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;">
                مصروف جديد
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="card" style="margin-bottom:20px;border-color:#b7e4c7;background:#f0fff4;color:#157347;">
            {{ session('success') }}
        </div>
    @endif

    <div class="card" style="margin-bottom:20px;">
        <form method="GET" action="{{ route('expenses.index') }}">
            <div style="display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:16px;align-items:end;">
                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">من تاريخ</label>
                    <input type="date" name="from_date" value="{{ $filters['from_date'] }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">إلى تاريخ</label>
                    <input type="date" name="to_date" value="{{ $filters['to_date'] }}"
                           style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">الفرع</label>
                    <select name="branch_id" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل الفروع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>
                                {{ $branch->name_ar ?? $branch->name ?? $branch->name_en ?? 'فرع #' . $branch->id }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">تصنيف المصروف</label>
                    <select name="expense_category_id" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل التصنيفات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) $filters['expense_category_id'] === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">طريقة الدفع</label>
                    <select name="payment_method" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل طرق الدفع</option>
                        @foreach ($paymentMethods as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_method'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">حالة الدفع</label>
                    <select name="payment_status" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل الحالات</option>
                        @foreach ($paymentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['payment_status'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="muted" style="display:block;margin-bottom:8px;">المرفقات</label>
                    <select name="attachment_status" style="width:100%;padding:12px;border:1px solid #e7dcd2;border-radius:12px;">
                        <option value="">كل المرفقات</option>
                        @foreach ($attachmentStatuses as $value => $label)
                            <option value="{{ $value }}" @selected($filters['attachment_status'] === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="submit"
                            style="background:#8b5e3c;color:#fff;border:0;padding:12px 20px;border-radius:12px;font-weight:700;cursor:pointer;">
                        تطبيق الفلتر
                    </button>

                    <a href="{{ route('expenses.index') }}"
                       style="display:inline-block;background:#eee4dc;color:#5d3b25;padding:12px 20px;border-radius:12px;font-weight:700;">
                        إعادة ضبط
                    </a>
                </div>
            </div>
        </form>
    </div>


    @if (request('large_amount') === '1')
        @php
            $largeAmountResetFilters = request()->except(['large_amount', 'page']);

            $largeAmountResetFilters = array_filter(
                $largeAmountResetFilters,
                fn ($value): bool => $value !== null && $value !== ''
            );
        @endphp

        <div class="card" data-testid="expense-large-amount-active-filter" style="margin-bottom:20px;border-color:#d39c35;background:#fff7ed;">
            <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
                <div>
                    <h2 style="margin-top:0;">فلتر المصاريف الكبيرة مفعّل</h2>
                    <div class="muted">
                        تعرض القائمة الحالية المصاريف التي تبلغ 1,000.00 ريال أو أكثر مع الحفاظ على الفلاتر الأخرى.
                    </div>
                </div>

                <a href="{{ route('expenses.index', $largeAmountResetFilters) }}"
                   style="background:#eee4dc;color:#5d3b25;padding:12px 18px;border-radius:12px;font-weight:700;">
                    إلغاء فلتر المصاريف الكبيرة
                </a>
            </div>
        </div>
    @endif
    <div class="card" data-testid="expense-large-amount-alert" style="margin-bottom:20px;border-color:#d39c35;background:#fffaf0;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">تنبيه المصاريف الكبيرة</h2>
                <div class="muted" style="margin-bottom:16px;">
                    يعرض هذا التنبيه المصاريف التي تبلغ
                    {{ number_format($largeAmountAlert['threshold'], 2) }}
                    ريال أو أكثر ضمن الفلاتر الحالية.
                </div>
            </div>

            <a href="{{ $largeAmountAlert['quick_filter_url'] }}"
               style="background:#b45309;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;">
                عرض المصاريف الكبيرة
            </a>
        </div>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">عدد المصاريف الكبيرة</div>
                <div class="metric-value">{{ $largeAmountAlert['count'] }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي قيمتها</div>
                <div class="metric-value">{{ number_format($largeAmountAlert['total_amount'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">أعلى مصروف ضمن الفلاتر الحالية</div>
                <div class="metric-value" style="font-size:18px;">
                    @if ($largeAmountAlert['highest'])
                        {{ $largeAmountAlert['highest']->description }}
                        <div style="margin-top:6px;font-size:24px;">
                            {{ number_format($largeAmountAlert['highest']->amount, 2) }} ريال
                        </div>
                    @else
                        لا يوجد
                    @endif
                </div>
            </div>
        </div>
    </div>
        <div class="card" data-testid="expense-large-unpaid-summary" style="margin-bottom:20px;border-color:#efb7a4;background:#fffaf7;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">ملخص المصاريف الكبيرة غير المدفوعة</h2>
                <div class="muted">
                    يعرض هذا الملخص عدد وإجمالي المصاريف التي تبلغ 1,000.00 ريال أو أكثر ولم يتم سدادها، ضمن الفلاتر الحالية.
                </div>

                <div style="margin-top:12px;">
                    <a
                        href="{{ route('expenses.export-large-unpaid', array_merge(request()->except('page'), ['large_amount' => '1', 'payment_status' => 'unpaid'])) }}"
                        class="btn"
                        data-testid="expense-large-unpaid-summary-export"
                    >
                        تصدير CSV للمصاريف الكبيرة غير المدفوعة
                    </a>
                </div>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <div style="min-width:160px;padding:12px 14px;border:1px solid #efc1b3;border-radius:10px;background:#fff;">
                    <div class="muted">العدد</div>
                    <strong data-testid="expense-large-unpaid-summary-count">{{ $largeUnpaidSummary['count'] }}</strong>
                </div>

                <div style="min-width:190px;padding:12px 14px;border:1px solid #efc1b3;border-radius:10px;background:#fff;">
                    <div class="muted">الإجمالي</div>
                    <strong data-testid="expense-large-unpaid-summary-total">{{ number_format((float) $largeUnpaidSummary['amount'], 2) }} ريال</strong>
                </div>
            </div>
        </div>
    </div>
    <div class="card" data-testid="expense-large-amount-top-list" style="margin-bottom:20px;border-color:#d9a441;background:#fffdf7;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">أعلى 5 مصاريف كبيرة</h2>
                <div class="muted" style="margin-bottom:16px;">
                    يعرض هذا الملخص أعلى المصاريف التي تبلغ 1,000.00 ريال أو أكثر ضمن الفلاتر الحالية.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('expenses.export-top-large', request()->query()) }}"
                    class="btn"
                    data-testid="expense-large-amount-top-list-export"
                >
                    تصدير أعلى 5 CSV
                </a>
            </div>
        </div>

        <div data-testid="expense-large-amount-top-list-total" style="margin:0 0 16px;padding:12px 14px;border:1px solid #ead7a5;border-radius:10px;background:#fff8e6;">
            <strong>إجمالي أعلى 5 مصاريف كبيرة:</strong>
            <span>{{ number_format((float) $largeAmountTopExpensesTotal, 2) }} ريال</span>
        </div>
        @if ($largeAmountTopExpenses->isEmpty())
            <div class="muted">
                لا توجد مصاريف كبيرة ضمن الفلاتر الحالية.
            </div>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>الترتيب</th>
                            <th>التاريخ</th>
                            <th>الوصف</th>
                            <th>الفرع</th>
                            <th>التصنيف</th>
                            <th>طريقة الدفع</th>
                            <th>حالة الدفع</th>
                            <th>المبلغ</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($largeAmountTopExpenses as $topExpense)
                            <tr
                                data-testid="expense-large-amount-top-expense-row-{{ $loop->iteration }}"
                                @if ($loop->first) style="background:#fff8e6;" @endif
                            >
                                <td>
                                    <strong>#{{ $loop->iteration }}</strong>
                                    @if ($loop->first)
                                        <span class="badge green" style="margin-inline-start:6px;">الأعلى</span>
                                    @endif
                                </td>
                                <td>{{ $topExpense->expense_date?->format('Y-m-d') }}</td>
                                <td>{{ $topExpense->description }}</td>
                                <td>{{ $topExpense->branch?->name_ar ?? $topExpense->branch?->name ?? $topExpense->branch?->name_en ?? '' }}</td>
                                <td>{{ $topExpense->category?->name ?? '' }}</td>
                                <td>{{ $topExpense->displayPaymentMethod() }}</td>
                                <td>
                                    @if ($topExpense->is_paid)
                                        <span class="badge green">مدفوع</span>
                                    @else
                                        <span class="badge red">غير مدفوع</span>
                                    @endif
                                </td>
                                <td>{{ number_format((float) $topExpense->amount, 2) }} ريال</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <div class="card" data-testid="expense-unpaid-alert" style="margin-bottom:20px;border-color:#f1b5b5;background:#fffafa;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">تنبيه المصاريف غير المدفوعة</h2>
                <div class="muted" style="margin-bottom:16px;">
                    يعرض هذا التنبيه المصاريف غير المدفوعة ضمن الفلاتر الحالية.
                </div>
            </div>

            <a href="{{ route('expenses.index', array_merge(request()->query(), ['payment_status' => 'unpaid'])) }}"
               style="background:#b42318;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;">
                عرض المصاريف غير المدفوعة
            </a>
        </div>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">عدد المصاريف غير المدفوعة</div>
                <div class="metric-value">{{ $unpaidAlert['count'] }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي قيمة المصاريف غير المدفوعة</div>
                <div class="metric-value">{{ number_format($unpaidAlert['total_amount'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">تاريخ أقدم مصروف غير مدفوع</div>
                <div class="metric-value" style="font-size:22px;">
                    {{ $unpaidAlert['oldest_date'] ?? 'لا يوجد' }}
                </div>
            </div>
        </div>

        <div class="grid" style="margin-top:18px;">
            <div class="metric">
                <div class="metric-label">أقدم مصروف غير مدفوع</div>
                <div class="metric-value" style="font-size:18px;">
                    @if ($unpaidAlert['oldest_expense'])
                        {{ $unpaidAlert['oldest_expense']->description }}
                        <div class="muted" style="font-size:13px;margin-top:8px;">
                            {{ $unpaidAlert['oldest_expense']->category?->name ?? 'بدون تصنيف' }}
                        </div>
                        <div style="margin-top:6px;font-size:24px;">
                            {{ number_format((float) $unpaidAlert['oldest_expense']->amount, 2) }} ريال
                        </div>
                    @else
                        لا توجد مصاريف غير مدفوعة ضمن الفلاتر الحالية
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="expense-monthly-summary" style="margin-bottom:20px;">
        <h2 style="margin-top:0;">ملخص مصاريف الشهر الحالي</h2>
        <div class="muted" style="margin-bottom:16px;">
            الشهر: {{ $monthlySummary['month_label'] }}
        </div>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">إجمالي مصاريف الشهر الحالي</div>
                <div class="metric-value">{{ number_format($monthlySummary['total_amount'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي المدفوع خلال الشهر</div>
                <div class="metric-value">{{ number_format($monthlySummary['paid_amount'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي غير المدفوع خلال الشهر</div>
                <div class="metric-value">{{ number_format($monthlySummary['unpaid_amount'], 2) }} ريال</div>
            </div>
        </div>

        <div class="grid" style="margin-top:18px;">
            <div class="metric">
                <div class="metric-label">أعلى تصنيف مصروف خلال الشهر</div>
                <div class="metric-value" style="font-size:18px;">
                    @if ($monthlySummary['top_category'])
                        {{ $monthlySummary['top_category']['name'] }}
                        <div style="margin-top:6px;font-size:24px;">
                            {{ number_format($monthlySummary['top_category']['amount'], 2) }} ريال
                        </div>
                    @else
                        لا توجد مصاريف هذا الشهر
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">إجمالي نتائج الفلتر</div>
            <div class="metric-value">{{ $expenseTotals['count'] }}</div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي المصاريف</div>
            <div class="metric-value">{{ number_format($expenseTotals['amount'], 2) }} ريال</div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي ضريبة المصاريف</div>
            <div class="metric-value">{{ number_format($expenseTotals['tax_amount'], 2) }} ريال</div>
        </div>
    </div>

    <div class="grid" style="margin-bottom:20px;">
        <div class="metric">
            <div class="metric-label">إجمالي المصاريف المدفوعة</div>
            <div class="metric-value">{{ number_format($expenseTotals['paid_amount'], 2) }} ريال</div>
        </div>

        <div class="metric">
            <div class="metric-label">إجمالي المصاريف غير المدفوعة</div>
            <div class="metric-value">{{ number_format($expenseTotals['unpaid_amount'], 2) }} ريال</div>
        </div>

        <div class="metric">
            <div class="metric-label">حالة الدفع المحددة</div>
            <div class="metric-value" style="font-size:18px;">
                {{ $filters['payment_status'] ? ($paymentStatuses[$filters['payment_status']] ?? $filters['payment_status']) : 'كل الحالات' }}
            </div>
        </div>
    </div>

    <div class="card" data-testid="expense-missing-attachment-summary" style="margin-bottom:20px;border-color:#ead7b7;background:#fffaf0;">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">ملخص المصاريف بدون مرفق</h2>
                <div class="muted" style="margin-bottom:16px;">
                    يساعد هذا الملخص في متابعة المصاريف التي تحتاج إيصالًا أو مستندًا ضمن الفلاتر الحالية.
                </div>
            </div>

            <a href="{{ route('expenses.index', array_merge(request()->query(), ['attachment_status' => 'without_attachment'])) }}"
               style="background:#8b5e3c;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;">
                عرض المصاريف بدون مرفق
            </a>
        </div>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">عدد المصاريف بدون مرفق</div>
                <div class="metric-value">{{ $missingAttachmentSummary['count'] }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي قيمة المصاريف بدون مرفق</div>
                <div class="metric-value">{{ number_format($missingAttachmentSummary['total_amount'], 2) }} ريال</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="expense-list">
        <h2 style="margin-top:0;">قائمة المصاريف</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>التاريخ</th>
                        <th>الوصف</th>
                        <th>الفرع</th>
                        <th>التصنيف</th>
                        <th>طريقة الدفع</th>
                        <th>المبلغ</th>
                        <th>الضريبة</th>
                        <th>حالة الدفع</th>
                        <th>المرفق</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $expense->code }}</td>
                            <td>{{ $expense->expense_date?->format('Y-m-d') }}</td>
                            <td>{{ $expense->description }}</td>
                            <td>{{ $expense->branch?->name_ar ?? $expense->branch?->name ?? $expense->branch?->name_en ?? '—' }}</td>
                            <td>{{ $expense->category?->name ?? '—' }}</td>
                            <td>{{ $expense->displayPaymentMethod() }}</td>
                            <td>{{ number_format((float) $expense->amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $expense->tax_amount, 2) }} ريال</td>
                            <td>
                                @if ($expense->is_paid)
                                    <span class="badge green">مدفوع</span>
                                @else
                                    <span class="badge gray">غير مدفوع</span>
                                @endif
                            </td>
                            <td>
                                @if ($expense->hasAttachment())
                                    <a href="{{ $expense->attachmentUrl() }}" target="_blank"
                                       style="color:#5d3b25;font-weight:700;">
                                        عرض المرفق
                                    </a>
                                    <div class="muted" style="font-size:12px;margin-top:4px;">
                                        {{ $expense->attachment_original_name }}
                                    </div>
                                @else
                                    <span class="muted">لا يوجد</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <a href="{{ route('expenses.edit', $expense) }}"
                                       style="background:#eee4dc;color:#5d3b25;padding:8px 12px;border-radius:10px;font-weight:700;">
                                        تعديل
                                    </a>

                                    <form method="POST" action="{{ route('expenses.destroy', $expense) }}" onsubmit="return confirm('هل تريد حذف هذا المصروف؟');">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                style="background:#b42318;color:#fff;border:0;padding:8px 12px;border-radius:10px;font-weight:700;cursor:pointer;">
                                            حذف
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11">لا توجد مصاريف ضمن الفلاتر الحالية.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
