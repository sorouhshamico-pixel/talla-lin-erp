<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الإيرادات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f7f7f7; color:#222; margin:0; padding:24px; }
        .page { max-width:1200px; margin:0 auto; }
        .card { background:#fff; border:1px solid #ddd; border-radius:12px; padding:20px; margin-bottom:20px; }
        .muted { color:#666; font-size:14px; }
        .btn { display:inline-block; padding:10px 14px; border-radius:8px; background:#111827; color:#fff; text-decoration:none; border:0; cursor:pointer; }
        .btn.secondary { background:#f3f4f6; color:#111827; border:1px solid #d1d5db; }
        .grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; }
        label { display:block; margin-bottom:6px; font-weight:bold; }
        input, select { width:100%; padding:9px; border:1px solid #ccc; border-radius:8px; box-sizing:border-box; }
        table { width:100%; border-collapse:collapse; background:#fff; }
        th, td { border-bottom:1px solid #eee; padding:10px; text-align:right; vertical-align:top; }
        th { background:#f9fafb; }
        .badge { display:inline-block; padding:4px 8px; border-radius:999px; font-size:12px; }
        .badge.green { background:#dcfce7; color:#166534; }
        .badge.red { background:#fee2e2; color:#991b1b; }
        .summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; }
        .summary-box { border:1px solid #ddd; border-radius:10px; padding:14px; background:#fafafa; }
        .alert-success { padding:12px 14px; border-radius:10px; background:#dcfce7; color:#166534; margin-bottom:16px; }
    </style>
</head>
<body>
<div class="page">
    <div class="card">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
            <div>
                <h1 style="margin:0 0 8px;">الإيرادات</h1>
                <div class="muted">إدارة إيرادات ومقبوضات الشركة حسب الفرع والتصنيف وطريقة التحصيل.</div>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="{{ route('revenue-categories.index') }}" class="btn secondary" data-testid="revenue-categories-link">تصنيفات الإيرادات</a>
                <a href="{{ route('revenues.export', request()->query()) }}" class="btn secondary" data-testid="revenue-export-link">تصدير CSV</a>
                <a href="{{ route('revenues.create') }}" class="btn" data-testid="revenue-create-link">إضافة إيراد</a>
        <a href="{{ route('revenues.uncollected.export') }}" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
            تصدير الإيرادات غير المحصلة CSV
        </a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    <div class="card" data-testid="revenue-summary">
        <h2 style="margin-top:0;">ملخص الإيرادات</h2>

        <div class="summary">
            <div class="summary-box">
                <div class="muted">العدد</div>
                <strong>{{ $revenueTotals['count'] }}</strong>
            </div>

            <div class="summary-box">
                <div class="muted">الإجمالي</div>
                <strong>{{ number_format((float) $revenueTotals['amount'], 2) }} ريال</strong>
            </div>

            <div class="summary-box">
                <div class="muted">الضريبة</div>
                <strong>{{ number_format((float) $revenueTotals['tax_amount'], 2) }} ريال</strong>
            </div>

            <div class="summary-box">
                <div class="muted">المحصل</div>
                <strong>{{ number_format((float) $revenueTotals['collected_amount'], 2) }} ريال</strong>
            </div>

            <div class="summary-box">
                <div class="muted">غير المحصل</div>
                <strong>{{ number_format((float) $revenueTotals['uncollected_amount'], 2) }} ريال</strong>
            </div>
        </div>
    </div>

    <div class="card" data-testid="revenue-uncollected-summary" style="border-color:#f2c0a2;background:#fffaf7;">
        <div style="display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">ملخص الإيرادات غير المحصلة</h2>
                <div class="muted">
                    يعرض هذا الملخص عدد وإجمالي الإيرادات غير المحصلة ضمن الفلاتر الحالية.
                </div>
            </div>

            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <div style="min-width:160px;padding:12px 14px;border:1px solid #f2c0a2;border-radius:10px;background:#fff;">
                    <div class="muted">العدد</div>
                    <strong data-testid="revenue-uncollected-summary-count">{{ $uncollectedRevenueSummary['count'] }}</strong>
                </div>

                <div style="min-width:190px;padding:12px 14px;border:1px solid #f2c0a2;border-radius:10px;background:#fff;">
                    <div class="muted">الإجمالي</div>
                    <strong data-testid="revenue-uncollected-summary-total">{{ number_format((float) $uncollectedRevenueSummary['amount'], 2) }} ريال</strong>
                </div>
            </div>
        </div>
    </div>
                
<div class="card" data-testid="revenue-uncollected-quick-filter-card" style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;" data-quick-filter-card="revenue" data-quick-filter-style="unified">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">فلتر الإيرادات غير المحصلة</h2>
                <div class="muted" style="margin-bottom:12px;">
                    استخدم هذا الفلتر السريع لعرض الإيرادات التي لم يتم تحصيلها بعد ضمن الفلاتر الحالية.
                </div>

                @if (($filters['collection_status'] ?? null) === 'uncollected')
                    <div data-testid="revenue-uncollected-quick-filter-active" style="display:inline-block;padding:7px 10px;border-radius:999px;background:#fff1ec;border:1px solid #f0b8a6;">
                        فلتر الإيرادات غير المحصلة مفعّل
                    </div>
                @endif
            </div>

            <div>
                <a
                    href="{{ route('revenues.index', array_merge(request()->except('page'), ['collection_status' => 'uncollected'])) }}"
                    class="btn"
                    data-testid="revenue-uncollected-quick-filter"
                >
                    عرض الإيرادات غير المحصلة
                </a>
            </div>
        </div>
    </div>

<div class="card" data-testid="revenue-active-quick-filter-card" style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;" data-quick-filter-card="revenue" data-quick-filter-style="unified">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">فلتر الإيرادات النشطة</h2>
                <div class="muted" style="margin-bottom:12px;">
                    استخدم هذا الفلتر السريع لعرض الإيرادات النشطة فقط مع الحفاظ على الفلاتر الحالية.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('revenues.index', array_merge(request()->query(), ['archive_status' => 'active'])) }}"
                    class="btn secondary"
                    data-testid="revenue-active-quick-filter"
                >
                    عرض الإيرادات النشطة
                </a>
            </div>
        </div>
    </div>

<div class="card" data-testid="revenue-archived-quick-filter-card" style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;" data-quick-filter-card="revenue" data-quick-filter-style="unified">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">فلتر الإيرادات المؤرشفة</h2>
                <div class="muted" style="margin-bottom:12px;">
                    استخدم هذا الفلتر السريع لعرض الإيرادات المؤرشفة مع الحفاظ على الفلاتر الحالية.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('revenues.index', array_merge(request()->query(), ['archive_status' => 'archived'])) }}"
                    class="btn secondary"
                    data-testid="revenue-archived-quick-filter"
                >
                    عرض الإيرادات المؤرشفة
                </a>
            </div>
        </div>
    </div>

<div class="card" data-testid="revenue-clear-archive-filter-card" style="margin-bottom:20px;border-color:#d1d5db;background:#ffffff;" data-quick-filter-card="revenue" data-quick-filter-style="unified">
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">إلغاء فلتر الأرشفة</h2>
                <div class="muted" style="margin-bottom:12px;">
                    استخدم هذا الزر لإزالة فلتر الأرشفة فقط مع الحفاظ على باقي فلاتر الإيرادات الحالية.
                </div>
            </div>

            <div>
                <a
                    href="{{ route('revenues.index', collect(request()->query())->except(['archive_status', 'archived'])->all()) }}"
                    class="btn secondary"
                    data-testid="revenue-clear-archive-filter"
                >
                    عرض كل حالات الأرشفة
                </a>
            </div>
        </div>
    </div>


    {{-- 12Q_REVENUE_ACTIVE_FILTER_COUNT_CARD --}}
    @php
        $revenueActiveFilters = collect(request()->query())
            ->except(['page'])
            ->filter(function ($value) {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($item) => filled($item))->isNotEmpty();
                }

                return filled($value);
            });

        $revenueActiveFilterCount = $revenueActiveFilters->count();
    @endphp

    <div
        class="card"
        data-testid="revenue-active-filter-count-card"
        data-active-filter-count="{{ $revenueActiveFilterCount }}"
        style="margin-bottom:20px;border-color:#dbeafe;background:#eff6ff;"
    >
        <div style="display:flex;justify-content:space-between;gap:12px;align-items:center;flex-wrap:wrap;">
            <div>
                <h2 style="margin-top:0;">عدد الفلاتر النشطة</h2>
                <div class="muted">
                    يعرض هذا العدّاد عدد فلاتر الإيرادات المطبقة حاليًا باستثناء ترقيم الصفحات.
                </div>
            </div>

            <div style="min-width:120px;text-align:center;padding:12px 16px;border:1px solid #bfdbfe;border-radius:10px;background:#fff;">
                <div class="muted">الفلاتر</div>
                <strong data-testid="revenue-active-filter-count">{{ $revenueActiveFilterCount }}</strong>
            </div>
        </div>
    </div>
    <div class="card">
        <h2 style="margin-top:0;">فلترة الإيرادات</h2>

        <form method="GET" action="{{ route('revenues.index') }}">
            <div class="grid">
                <div>
                    <label for="branch_id">الفرع</label>
                    <select name="branch_id" id="branch_id">
                        <option value="">كل الفروع</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>
                                {{ $branch->name_ar ?? $branch->name ?? $branch->name_en ?? 'فرع' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="revenue_category_id">التصنيف</label>
                    <select name="revenue_category_id" id="revenue_category_id">
                        <option value="">كل التصنيفات</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['revenue_category_id'] ?? '') === (string) $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="collection_method">طريقة التحصيل</label>
                    <select name="collection_method" id="collection_method">
                        <option value="">كل الطرق</option>
                        @foreach ($collectionMethods as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['collection_method'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="collection_status">حالة التحصيل</label>
                    <select name="collection_status" id="collection_status">
                        <option value="">كل الحالات</option>
                        @foreach ($collectionStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['collection_status'] ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="archive_status">حالة الأرشفة</label>
                    <select name="archive_status" id="archive_status" data-testid="revenue-archive-status-filter">
                        @foreach ($archiveStatuses as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['archive_status'] ?? 'active') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="date_from">من تاريخ</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] ?? '' }}">
                </div>

                <div>
                    <label for="date_to">إلى تاريخ</label>
                    <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] ?? '' }}">
                </div>
            </div>

            <div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap;">
                <button type="submit" class="btn">تطبيق الفلتر</button>
                <a href="{{ route('revenues.index') }}" class="btn secondary">إلغاء الفلتر</a>
            </div>
        </form>
    </div>

    <div class="card" data-testid="revenues-table">
        <h2 style="margin-top:0;">قائمة الإيرادات</h2>

        @if (($filters['archive_status'] ?? 'active') === 'archived')
            <div data-testid="revenue-archived-list-notice" style="margin-bottom:12px;padding:10px 12px;border-radius:10px;background:#fef3c7;color:#92400e;">
                يتم الآن عرض الإيرادات المؤرشفة فقط.
            </div>
        @endif

        @if ($revenues->isEmpty())
            <div class="muted">لا توجد إيرادات مسجلة.</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>الكود</th>
                        <th>التاريخ</th>
                        <th>الوصف</th>
                        <th>الفرع</th>
                        <th>التصنيف</th>
                        <th>طريقة التحصيل</th>
                        <th>حالة التحصيل</th>
                        <th>المبلغ</th>
                        <th>الضريبة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($revenues as $revenue)
                        <tr>
                            <td>{{ $revenue->code }}</td>
                            <td>{{ $revenue->revenue_date?->format('Y-m-d') }}</td>
                            <td>{{ $revenue->description }}</td>
                            <td>{{ $revenue->branch?->name_ar ?? $revenue->branch?->name ?? $revenue->branch?->name_en ?? '' }}</td>
                            <td>{{ $revenue->category?->name ?? '' }}</td>
                            <td>{{ $revenue->displayCollectionMethod() }}</td>
                            <td>
                                @if ($revenue->is_collected)
                                    <span class="badge green">محصل</span>
                                @else
                                    <span class="badge red">غير محصل</span>
                                @endif
                            </td>
                            <td>{{ number_format((float) $revenue->amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $revenue->tax_amount, 2) }} ريال</td>
                            <td>
                                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                                    <a
                                        href="{{ route('revenues.edit', $revenue) }}"
                                        class="btn secondary"
                                        data-testid="revenue-edit-link-{{ $revenue->id }}"
                                    >
                                        تعديل
                                    </a>

                                    <form method="POST" action="{{ route('revenues.toggle-collection', $revenue) }}">
                                        @csrf
                                        @method('PATCH')

                                        <button
                                            type="submit"
                                            class="btn secondary"
                                            data-testid="revenue-toggle-collection-button-{{ $revenue->id }}"
                                        >
                                            {{ $revenue->is_collected ? 'تعليم كغير محصل' : 'تعليم كمحصل' }}
                                        </button>
                                    </form>

                                    @if ($revenue->archived_at)
                                        <form method="POST" action="{{ route('revenues.restore', $revenue) }}">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn secondary"
                                                data-testid="revenue-restore-button-{{ $revenue->id }}"
                                            >
                                                استعادة
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('revenues.archive', $revenue) }}" onsubmit="return confirm('هل تريد أرشفة هذا الإيراد؟');">
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn secondary"
                                                data-testid="revenue-archive-button-{{ $revenue->id }}"
                                            >
                                                أرشفة
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
</body>
</html>
