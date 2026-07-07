@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="page-header">
            <div>
                <h1>لوحة التدفق النقدي المتوقع</h1>
                <p>ملخص سريع للتدفقات النقدية المتوقعة من ذمم العملاء والتزامات الموردين.</p>
            </div>

            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">مركز التقارير</a>
        </div>

        <div class="card" data-testid="cash-flow-dashboard">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.cash-flow-dashboard.index') }}" class="filters" data-testid="cash-flow-dashboard-filters">
                    <div class="filter-row">
                        <label for="branch_id">الفرع</label>
                        <select name="branch_id" id="branch_id" data-testid="cash-flow-branch-select">
                            <option value="">كل الفروع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>
                                    {{ $branch->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="filter-row">
                        <label for="date_from">من تاريخ الاستحقاق</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $selectedDateFrom }}" data-testid="cash-flow-date-from-input">
                    </div>

                    <div class="filter-row">
                        <label for="date_to">إلى تاريخ الاستحقاق</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $selectedDateTo }}" data-testid="cash-flow-date-to-input">
                    </div>

                    <div class="report-actions" data-testid="cash-flow-date-range-presets">
                        <a href="{{ route('reports.cash-flow-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'date_from' => now()->copy()->startOfMonth()->format('Y-m-d'), 'date_to' => now()->copy()->endOfMonth()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="cash-flow-date-range-preset-current-month">الشهر الحالي</a>
                        <a href="{{ route('reports.cash-flow-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'date_from' => now()->format('Y-m-d'), 'date_to' => now()->copy()->addDays(30)->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="cash-flow-date-range-preset-next-30-days">الثلاثون يومًا القادمة</a>
                        <a href="{{ route('reports.cash-flow-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'date_from' => now()->copy()->addMonthNoOverflow()->startOfMonth()->format('Y-m-d'), 'date_to' => now()->copy()->addMonthNoOverflow()->endOfMonth()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="cash-flow-date-range-preset-next-month">الشهر القادم</a>
                        <a href="{{ route('reports.cash-flow-dashboard.index', array_filter(['branch_id' => $selectedBranchId, 'date_to' => now()->format('Y-m-d')])) }}" class="btn btn-outline-secondary" data-testid="cash-flow-date-range-preset-until-today">حتى اليوم</a>
                    </div>

                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary" data-testid="cash-flow-apply-filters">تطبيق الفلاتر</button>
                        <a href="{{ route('reports.cash-flow-dashboard.index') }}" class="btn btn-outline-secondary" data-testid="cash-flow-reset-filters">إعادة تعيين</a>
                    </div>
                </form>

                <p data-testid="cash-flow-dashboard-report-date">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</p>

                <div class="summary-grid" data-testid="cash-flow-inflow-summary">
                    <div class="summary-card">
                        <span>عدد العملاء أصحاب الذمم</span>
                        <strong>{{ $inflowSummary['customers_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>فواتير العملاء المفتوحة</span>
                        <strong>{{ $inflowSummary['open_invoice_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>التدفقات الداخلة المتوقعة</span>
                        <strong>{{ number_format((float) $inflowSummary['expected_inflows'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>تدفقات داخلة متأخرة</span>
                        <strong>{{ number_format((float) $inflowSummary['overdue_inflows'], 2) }} ريال</strong>
                    </div>
                </div>

                <div class="summary-grid" data-testid="cash-flow-outflow-summary">
                    <div class="summary-card">
                        <span>عدد الموردين أصحاب الذمم</span>
                        <strong>{{ $outflowSummary['suppliers_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>فواتير الموردين المفتوحة</span>
                        <strong>{{ $outflowSummary['open_invoice_count'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>التدفقات الخارجة المتوقعة</span>
                        <strong>{{ number_format((float) $outflowSummary['expected_outflows'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>تدفقات خارجة متأخرة</span>
                        <strong>{{ number_format((float) $outflowSummary['overdue_outflows'], 2) }} ريال</strong>
                    </div>
                </div>

                <div class="summary-grid" data-testid="cash-flow-net-summary">
                    <div class="summary-card">
                        <span>صافي التدفق النقدي المتوقع</span>
                        <strong>{{ number_format((float) $netCashSummary['net_expected_cash'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>حالة التدفق النقدي المتوقع</span>
                        <strong>{{ $netCashSummary['position_label'] }}</strong>
                    </div>
                </div>

                <div class="summary-grid" data-testid="cash-flow-risk-summary">
                    <div class="summary-card">
                        <span>إجمالي التدفقات الداخلة المتأخرة</span>
                        <strong>{{ number_format((float) $riskSummary['overdue_inflows'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>إجمالي التدفقات الخارجة المتأخرة</span>
                        <strong>{{ number_format((float) $riskSummary['overdue_outflows'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>صافي الضغط النقدي المتأخر</span>
                        <strong>{{ number_format((float) $riskSummary['net_overdue_pressure'], 2) }} ريال</strong>
                    </div>

                    <div class="summary-card">
                        <span>حالة الضغط النقدي</span>
                        <strong>{{ $riskSummary['pressure_label'] }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>نسبة تغطية الالتزامات المتوقعة</span>
                        <strong>{{ $riskSummary['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $riskSummary['cash_coverage_ratio'], 2) . '%' }}</strong>
                    </div>

                    <div class="summary-card">
                        <span>حالة التغطية النقدية</span>
                        <strong>{{ $riskSummary['coverage_label'] }}</strong>
                    </div>
                </div>

                <div class="table-responsive" data-testid="cash-flow-bucket-comparison">
                    <h2>التدفق النقدي حسب شرائح الأعمار</h2>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>شريحة العمر</th>
                                <th>تدفقات داخلة متوقعة</th>
                                <th>تدفقات خارجة متوقعة</th>
                                <th>صافي التدفق النقدي</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bucketCashFlow as $bucket)
                                @php($bucketKey = str_replace('_total', '', $bucket['key']))
                                <tr>
                                    <td>{{ $bucket['label'] }}</td>
                                    <td>
                                        <a href="{{ route('reports.customer-sales-invoice-aging.drilldown', array_merge($drilldownParams, ['aging_bucket' => $bucketKey])) }}" data-testid="cash-flow-customer-bucket-drilldown-{{ $bucketKey }}">
                                            {{ number_format((float) $bucket['expected_inflows'], 2) }} ريال
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', array_merge($drilldownParams, ['aging_bucket' => $bucketKey])) }}" data-testid="cash-flow-supplier-bucket-drilldown-{{ $bucketKey }}">
                                            {{ number_format((float) $bucket['expected_outflows'], 2) }} ريال
                                        </a>
                                    </td>
                                    <td>{{ number_format((float) $bucket['net_cash_flow'], 2) }} ريال</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="report-actions">
                    <a href="{{ route('reports.cash-flow-dashboard.print', $filterParams) }}" class="btn btn-outline-secondary" data-testid="cash-flow-print-link">طباعة اللوحة</a>
                    <a href="{{ route('reports.cash-flow-dashboard.export', $filterParams) }}" class="btn btn-outline-primary" data-testid="cash-flow-export-link">تصدير CSV</a>
                    <a href="{{ route('reports.customer-sales-invoice-aging.index') }}" class="btn btn-outline-primary" data-testid="cash-flow-customer-aging-link">تقرير أعمار ذمم العملاء</a>
                    <a href="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="btn btn-outline-primary" data-testid="cash-flow-supplier-aging-link">تقرير أعمار ذمم الموردين</a>
                    <a href="{{ route('reports.receivable-payable-aging-dashboard.index') }}" class="btn btn-outline-primary" data-testid="cash-flow-aging-dashboard-link">لوحة أعمار الذمم</a>
                </div>
            </div>
        </div>
    </div>
@endsection
