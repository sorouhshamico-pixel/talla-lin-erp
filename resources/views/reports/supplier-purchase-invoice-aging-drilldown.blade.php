@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>تفاصيل فواتير الموردين المفتوحة</h1>
                <p>عرض تفصيلي لفواتير المشتريات المفتوحة حسب المورد وشريحة العمر.</p>
            </div>

            <a href="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="btn btn-outline-secondary">تقرير أعمار ذمم الموردين</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success" data-testid="supplier-aging-drilldown-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" data-testid="supplier-purchase-invoice-aging-drilldown">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.supplier-purchase-invoice-aging.drilldown') }}" class="filters" data-testid="supplier-aging-drilldown-filters">
                    <div class="filter-row">
                        <label for="supplier_id">المورد</label>
                        <select name="supplier_id" id="supplier_id" data-testid="supplier-aging-drilldown-supplier-select">
                            <option value="">كل الموردين</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) $selectedSupplierId === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="filter-row">
                        <label for="branch_id">الفرع</label>
                        <select name="branch_id" id="branch_id" data-testid="supplier-aging-drilldown-branch-select">
                            <option value="">كل الفروع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row">
                        <label for="as_of_date">تاريخ التقرير</label>
                        <input type="date" name="as_of_date" id="as_of_date" value="{{ $selectedAsOfDate }}" data-testid="supplier-aging-drilldown-as-of-date-input">

                        <div class="report-actions" data-testid="supplier-aging-drilldown-date-presets">
                            <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', array_filter(['supplier_id' => $selectedSupplierId, 'branch_id' => $selectedBranchId, 'as_of_date' => now()->format('Y-m-d'), 'aging_bucket' => $selectedAgingBucket])) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-drilldown-date-preset-today">اليوم</a>
                            <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', array_filter(['supplier_id' => $selectedSupplierId, 'branch_id' => $selectedBranchId, 'as_of_date' => now()->copy()->endOfMonth()->format('Y-m-d'), 'aging_bucket' => $selectedAgingBucket])) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-drilldown-date-preset-month-end">نهاية الشهر</a>
                            <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', array_filter(['supplier_id' => $selectedSupplierId, 'branch_id' => $selectedBranchId, 'as_of_date' => now()->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d'), 'aging_bucket' => $selectedAgingBucket])) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-drilldown-date-preset-previous-month-end">نهاية الشهر السابق</a>
                            <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', array_filter(['supplier_id' => $selectedSupplierId, 'branch_id' => $selectedBranchId, 'as_of_date' => now()->copy()->endOfQuarter()->format('Y-m-d'), 'aging_bucket' => $selectedAgingBucket])) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-drilldown-date-preset-quarter-end">نهاية الربع</a>
                        </div>
                    </div>

                    <div class="filter-row">
                        <label for="aging_bucket">شريحة العمر</label>
                        <select name="aging_bucket" id="aging_bucket" data-testid="supplier-aging-drilldown-bucket-select">
                            <option value="">كل الشرائح</option>
                            @foreach ($agingBuckets as $key => $label)
                                <option value="{{ $key }}" @selected($selectedAgingBucket === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="supplier-aging-drilldown-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown.export', request()->only(['supplier_id', 'branch_id', 'as_of_date', 'aging_bucket'])) }}" class="btn btn-outline-primary" data-testid="supplier-aging-drilldown-export-link">تصدير CSV</a>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', ['reset_filters' => 1]) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-drilldown-reset-filters">إعادة تعيين</a>
                    </div>
                </form>

                <div class="card" data-testid="supplier-aging-drilldown-save-view-card" style="margin-bottom:16px;">
                    <div class="card-body">
                        <h2>حفظ عرض التفاصيل</h2>


                @php
                    $supplierAgingDrilldownSavedViews = $savedViews ?? collect();
                @endphp

                <div class="card" data-testid="supplier-aging-drilldown-saved-views-selector" style="margin-bottom:16px;">
                    <div class="card-body">
                        <h2>العروض المحفوظة</h2>
        @include('reports.partials.active-saved-view-banner', ['savedViews' => $supplierAgingDrilldownSavedViews])

                        @if ($supplierAgingDrilldownSavedViews->isEmpty())
                            <p data-testid="supplier-aging-drilldown-saved-views-empty">
                                لا توجد عروض محفوظة لهذه التفاصيل حتى الآن.
                            </p>
                        @else
                            <div class="saved-views-list" data-testid="supplier-aging-drilldown-saved-views-list">
                                @foreach ($supplierAgingDrilldownSavedViews as $savedView)
                                    @php
                                        $savedViewFilters = array_filter(
                                            $savedView->filters ?? [],
                                            fn ($value) => $value !== null && $value !== ''
                                        );

                                        $savedViewRouteFilters = array_merge($savedViewFilters, [
                                            'saved_view_id' => $savedView->id,
                                        ]);

                                        $isActiveSavedView = (int) request('saved_view_id') === (int) $savedView->id;
                                    @endphp

                                    <div class="saved-view-row{{ $isActiveSavedView ? ' active-saved-view-row' : '' }}" data-testid="supplier-aging-drilldown-saved-view-item">
                                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', $savedViewRouteFilters) }}"
                                           data-testid="supplier-aging-drilldown-saved-view-open-link">
                                            {{ $savedView->name }}
                                        </a>

                                        @if ($isActiveSavedView)
                                            <span data-testid="supplier-aging-drilldown-saved-view-active-badge">نشط</span>
                                        @endif

                                        @if ($savedView->is_default)
                                            <span data-testid="supplier-aging-drilldown-saved-view-default-badge">افتراضي</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div style="margin-top:12px;">
                            <a href="{{ route('reports.saved-views.index') }}" data-testid="supplier-aging-drilldown-manage-saved-views-link">
                                إدارة العروض المحفوظة
                            </a>
                        </div>
                    </div>
                </div>

                        <form method="POST" action="{{ route('reports.supplier-purchase-invoice-aging.drilldown.saved-views.store') }}" data-testid="supplier-aging-drilldown-save-view-form">
                            @csrf

                            <input type="hidden" name="supplier_id" value="{{ $selectedSupplierId }}">
                            <input type="hidden" name="branch_id" value="{{ $selectedBranchId }}">
                            <input type="hidden" name="as_of_date" value="{{ $selectedAsOfDate }}">
                            <input type="hidden" name="aging_bucket" value="{{ $selectedAgingBucket }}">

                            <div class="filter-row">
                                <label for="supplier-aging-drilldown-save-view-form_name">اسم العرض المحفوظ</label>
                                <input id="supplier-aging-drilldown-save-view-form_name"
                                       type="text"
                                       name="name"
                                       placeholder="مثال: تفاصيل موردين نهاية الشهر"
                                       required
                                       maxlength="120"
                                       data-testid="supplier-aging-drilldown-saved-view-name-input">
                            </div>

                            <div class="filter-row">
                                <label>
                                    <input type="checkbox" name="is_default" value="1" data-testid="supplier-aging-drilldown-saved-view-default-checkbox">
                                    تعيين كعرض افتراضي لهذه التفاصيل
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary" data-testid="supplier-aging-drilldown-save-view-button">
                                حفظ العرض
                            </button>
                        </form>
                    </div>
                </div>



                <div class="report-meta">
                    <p data-testid="supplier-aging-drilldown-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>
                    <p data-testid="supplier-aging-drilldown-supplier-filter">فلتر المورد: {{ $selectedSupplierLabel }}</p>
                    <p data-testid="supplier-aging-drilldown-branch-filter">فلتر الفرع: {{ $selectedBranchLabel }}</p>
                    <p data-testid="supplier-aging-drilldown-as-of-date-filter">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>
                    <p data-testid="supplier-aging-drilldown-bucket-filter">فلتر شريحة العمر: {{ $selectedAgingBucketLabel }}</p>
                </div>

                <div class="summary-grid" data-testid="supplier-aging-drilldown-summary">
                    <div class="summary-card">
                        <span>عدد الفواتير المفتوحة</span>
                        <strong>{{ $summary['invoice_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي الفواتير</span>
                        <strong>{{ number_format((float) $summary['grand_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي المدفوع</span>
                        <strong>{{ number_format((float) $summary['paid_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي المتبقي</span>
                        <strong>{{ number_format((float) $summary['remaining_total'], 2) }} ريال</strong>
                    </div>
                </div>

                @if ($invoices->isEmpty())
                    <div class="empty-state" data-testid="supplier-aging-drilldown-empty">
                        لا توجد فواتير موردين مفتوحة حسب الفلاتر الحالية.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="supplier-aging-drilldown-table">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>المورد</th>
                                    <th>تاريخ الإصدار</th>
                                    <th>تاريخ الاستحقاق</th>
                                    <th>الإجمالي</th>
                                    <th>المدفوع</th>
                                    <th>المتبقي</th>
                                    <th>حالة الدفع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $supplierNames[$invoice->supplier_id] ?? '' }}</td>
                                        <td>{{ $invoice->issued_at ? \Illuminate\Support\Carbon::parse($invoice->issued_at)->format('Y-m-d') : '' }}</td>
                                        <td>{{ $invoice->due_at ? \Illuminate\Support\Carbon::parse($invoice->due_at)->format('Y-m-d') : '' }}</td>
                                        <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                                        <td>{{ number_format((float) $invoice->paid_amount, 2) }} ريال</td>
                                        <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                                        <td>{{ $invoice->payment_status }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
