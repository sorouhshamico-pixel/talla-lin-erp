@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>تقرير أعمار ذمم الموردين</h1>
                <p>متابعة فواتير المشتريات المفتوحة حسب المورد وشرائح العمر.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">مركز التقارير</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success" data-testid="supplier-aging-status">
                {{ session('status') }}
            </div>
        @endif

        <div class="card" data-testid="supplier-purchase-invoice-aging-report">
            <div class="card-body">

                <form method="GET" action="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="filters" data-testid="supplier-aging-filters">
                    <div class="filter-row">
                        <label for="supplier_id">المورد</label>
                        <select name="supplier_id" id="supplier_id" data-testid="supplier-aging-supplier-select">
                            <option value="">كل الموردين</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row">
                        <label for="aging_bucket">شريحة العمر</label>
                        <select name="aging_bucket" id="aging_bucket" data-testid="supplier-aging-bucket-select">
                            <option value="">كل الشرائح</option>
                            @foreach ($agingBuckets as $value => $label)
                                <option value="{{ $value }}" @selected(request('aging_bucket') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="supplier-aging-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.index', ['reset_filters' => 1]) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-reset-filters">إعادة تعيين</a>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', request()->only(['supplier_id', 'aging_bucket'])) }}" class="btn btn-outline-primary" data-testid="supplier-aging-drilldown-link">تفاصيل الفواتير المفتوحة</a>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.export', request()->only(['supplier_id', 'aging_bucket'])) }}" class="btn btn-outline-primary" data-testid="supplier-aging-export-button">تصدير CSV</a>
                        <a href="{{ route('reports.supplier-purchase-invoice-aging.print', request()->only(['supplier_id', 'aging_bucket'])) }}" class="btn btn-outline-secondary" data-testid="supplier-aging-print-link">طباعة التقرير</a>
                    </div>
                </form>

                <div class="card" data-testid="supplier-aging-save-view-card" style="margin-bottom:16px;">
                    <div class="card-body">
                        <h2>حفظ عرض التقرير</h2>


                @php
                    $supplierAgingSavedViews = $savedViews ?? collect();
                @endphp

                <div class="card" data-testid="supplier-aging-saved-views-selector" style="margin-bottom:16px;">
                    <div class="card-body">
                        <h2>العروض المحفوظة</h2>
        @include('reports.partials.active-saved-view-banner', ['savedViews' => $supplierAgingSavedViews])

                        @if ($supplierAgingSavedViews->isEmpty())
                            <p data-testid="supplier-aging-saved-views-empty">
                                لا توجد عروض محفوظة لهذا التقرير حتى الآن.
                            </p>
                        @else
                            <div class="saved-views-list" data-testid="supplier-aging-saved-views-list">
                                @foreach ($supplierAgingSavedViews as $savedView)
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

                                    <div class="saved-view-row{{ $isActiveSavedView ? ' active-saved-view-row' : '' }}" data-testid="supplier-aging-saved-view-item">
                                        <a href="{{ route('reports.supplier-purchase-invoice-aging.index', $savedViewRouteFilters) }}"
                                           data-testid="supplier-aging-saved-view-open-link">
                                            {{ $savedView->name }}
                                        </a>

                                        @if ($isActiveSavedView)
                                            <span data-testid="supplier-aging-saved-view-active-badge">نشط</span>
                                        @endif

                                        @if ($savedView->is_default)
                                            <span data-testid="supplier-aging-saved-view-default-badge">افتراضي</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <div style="margin-top:12px;">
                            <a href="{{ route('reports.saved-views.index') }}" data-testid="supplier-aging-manage-saved-views-link">
                                إدارة العروض المحفوظة
                            </a>
                        </div>
                    </div>
                </div>

                        <form method="POST" action="{{ route('reports.supplier-purchase-invoice-aging.saved-views.store') }}" data-testid="supplier-aging-save-view-form">
                            @csrf

                            <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">
                            <input type="hidden" name="aging_bucket" value="{{ request('aging_bucket') }}">

                            <div class="filter-row">
                                <label for="supplier_aging_saved_view_name">اسم العرض المحفوظ</label>
                                <input id="supplier_aging_saved_view_name"
                                       type="text"
                                       name="name"
                                       placeholder="مثال: متابعة ذمم الموردين"
                                       required
                                       maxlength="120"
                                       data-testid="supplier-aging-saved-view-name-input">
                            </div>

                            <div class="filter-row">
                                <label>
                                    <input type="checkbox" name="is_default" value="1" data-testid="supplier-aging-saved-view-default-checkbox">
                                    تعيين كعرض افتراضي لهذا التقرير
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary" data-testid="supplier-aging-save-view-button">حفظ العرض</button>
                        </form>
                    </div>
                </div>



                <div class="report-meta">
                    <p data-testid="supplier-aging-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>
                    <p data-testid="supplier-aging-supplier-filter">فلتر المورد: {{ $supplierFilterLabel }}</p>
                    <p data-testid="supplier-aging-bucket-filter">فلتر شريحة العمر: {{ $agingBucketFilterLabel }}</p>
                </div>

                <div class="summary-grid" data-testid="supplier-aging-summary">
                    <div class="summary-card">
                        <span>عدد الموردين</span>
                        <strong>{{ $summary['suppliers_count'] }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>عدد الفواتير المفتوحة</span>
                        <strong>{{ $summary['invoice_count'] }}</strong>
                    </div>
                    <div class="summary-card">
                        <span>إجمالي الذمم المفتوحة</span>
                        <strong>{{ number_format((float) $summary['remaining_total'], 2) }} ريال</strong>
                    </div>
                    <div class="summary-card">
                        <span>إجمالي المتأخر</span>
                        <strong>{{ number_format((float) $summary['overdue_total'], 2) }} ريال</strong>
                    </div>
                </div>

                @if ($rows->isEmpty())
                    <div class="empty-state" data-testid="supplier-aging-empty">
                        لا توجد ذمم مفتوحة للموردين.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="supplier-aging-table">
                            <thead>
                                <tr>
                                    <th>المورد</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المتبقي</th>
                                    <th>غير مستحقة بعد</th>
                                    <th>متأخرة 1 إلى 30</th>
                                    <th>متأخرة 31 إلى 60</th>
                                    <th>متأخرة 61 إلى 90</th>
                                    <th>أكثر من 90</th>
                                    <th>بدون تاريخ استحقاق</th>
                                    <th>أقدم استحقاق</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>{{ $row['supplier'] ? $row['supplier']->name : '' }}</td>
                                        <td>{{ $row['invoice_count'] }}</td>
                                        <td>{{ number_format((float) $row['remaining_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['not_due_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_1_30_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_31_60_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_61_90_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['overdue_more_than_90_total'], 2) }} ريال</td>
                                        <td>{{ number_format((float) $row['without_due_date_total'], 2) }} ريال</td>
                                        <td>{{ $row['oldest_due_at'] ? $row['oldest_due_at']->format('Y-m-d') : '' }}</td>
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
