@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>لوحة أعمار الذمم</h1>
                <p>ملخص سريع لأعمار ذمم العملاء والموردين من التقارير المعتمدة.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">مركز التقارير</a>
        </div>

        <div class="card" data-testid="receivable-payable-aging-dashboard">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.receivable-payable-aging-dashboard.index') }}" class="filters" data-testid="aging-dashboard-filters">
                    <div class="filter-row">
                        <label for="branch_id">الفرع</label>
                        <select name="branch_id" id="branch_id" data-testid="aging-dashboard-branch-select">
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
                        <input type="date" name="as_of_date" id="as_of_date" value="{{ $selectedAsOfDate }}" data-testid="aging-dashboard-as-of-date-input">
                    </div>

                    <div class="report-actions" data-testid="aging-dashboard-report-date-presets">
                        <a href="{{ route('reports.receivable-payable-aging-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'as_of_date' => now()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="aging-dashboard-preset-today">اليوم</a>
                        <a href="{{ route('reports.receivable-payable-aging-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'as_of_date' => now()->copy()->endOfMonth()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="aging-dashboard-preset-current-month-end">نهاية الشهر الحالي</a>
                        <a href="{{ route('reports.receivable-payable-aging-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'as_of_date' => now()->copy()->addMonthNoOverflow()->endOfMonth()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="aging-dashboard-preset-next-month-end">نهاية الشهر القادم</a>
                        <a href="{{ route('reports.receivable-payable-aging-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'as_of_date' => now()->copy()->endOfQuarter()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="aging-dashboard-preset-current-quarter-end">نهاية الربع الحالي</a>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="aging-dashboard-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.receivable-payable-aging-dashboard.index') }}" class="btn btn-outline-secondary" data-testid="aging-dashboard-reset-filters">إعادة تعيين</a>
                    </div>
                </form>

                <p data-testid="aging-dashboard-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>

                <div class="summary-grid" data-testid="aging-dashboard-customer-summary">
                    <div class="summary-card">
                        <span>عدد العملاء</span>
                        <strong>{{ $customerSummary['customers_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>فواتير العملاء المفتوحة</span>
                        <strong>{{ $customerSummary['invoice_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي ذمم العملاء المفتوحة</span>
                        <strong>{{ number_format((float) $customerSummary['remaining_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي المتأخر على العملاء</span>
                        <strong>{{ number_format((float) $customerSummary['overdue_total'], 2) }} ريال</strong>
                    </div>
                </div>

                <div class="summary-grid" data-testid="aging-dashboard-supplier-summary">
                    <div class="summary-card">
                        <span>عدد الموردين</span>
                        <strong>{{ $supplierSummary['suppliers_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>فواتير الموردين المفتوحة</span>
                        <strong>{{ $supplierSummary['invoice_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي ذمم الموردين المفتوحة</span>
                        <strong>{{ number_format((float) $supplierSummary['remaining_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي المتأخر للموردين</span>
                        <strong>{{ number_format((float) $supplierSummary['overdue_total'], 2) }} ريال</strong>
                    </div>
                </div>

                <div class="summary-grid" data-testid="aging-dashboard-net-summary">
                    <div class="summary-card">
                        <span>صافي الذمم المفتوحة</span>
                        <strong>{{ number_format((float) $netSummary['net_open_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>حالة صافي الذمم</span>
                        <strong>{{ $netSummary['position_label'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>صافي المتأخرات</span>
                        <strong>{{ number_format((float) $netSummary['net_overdue_total'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>حالة صافي المتأخرات</span>
                        <strong>{{ $netSummary['overdue_position_label'] }}</strong>
                    </div>
                </div>

                <div class="table-responsive" data-testid="aging-dashboard-bucket-comparison">
                    <h2>مقارنة شرائح الأعمار</h2>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>شريحة العمر</th>
                                <th>ذمم العملاء</th>
                                <th>ذمم الموردين</th>
                                <th>صافي الفرق</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bucketComparison as $bucket)
                                @php($bucketKey = str_replace('_total', '', $bucket['key']))
                                <tr>
                                    <td>{{ $bucket['label'] }}</td>
                                    <td>
                                        <a href="{{ route('reports.customer-sales-invoice-aging.drilldown', array_merge($drilldownParams, ['aging_bucket' => $bucketKey])) }}" data-testid="aging-dashboard-customer-bucket-drilldown-{{ $bucketKey }}">
                                            {{ number_format((float) $bucket['customer_total'], 2) }} ريال
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', array_merge($drilldownParams, ['aging_bucket' => $bucketKey])) }}" data-testid="aging-dashboard-supplier-bucket-drilldown-{{ $bucketKey }}">
                                            {{ number_format((float) $bucket['supplier_total'], 2) }} ريال
                                        </a>
                                    </td>
                                    <td>{{ number_format((float) $bucket['net_total'], 2) }} ريال</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="report-actions">
                    <a href="{{ route('reports.receivable-payable-aging-dashboard.print', $filterParams) }}" class="btn btn-outline-secondary" data-testid="aging-dashboard-print-link">طباعة اللوحة</a>
                    <a href="{{ route('reports.receivable-payable-aging-dashboard.export', $filterParams) }}" class="btn btn-outline-primary" data-testid="aging-dashboard-export-link">تصدير CSV</a>
                    <a href="{{ route('reports.customer-sales-invoice-aging.index', $filterParams) }}" class="btn btn-outline-primary" data-testid="aging-dashboard-customer-report-link">تقرير أعمار ذمم العملاء</a>
                    <a href="{{ route('reports.supplier-purchase-invoice-aging.index', $filterParams) }}" class="btn btn-outline-primary" data-testid="aging-dashboard-supplier-report-link">تقرير أعمار ذمم الموردين</a>
                </div>
            </div>
        </div>
    </div>
@endsection
