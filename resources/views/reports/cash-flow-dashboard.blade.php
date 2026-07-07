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

                <div class="report-actions">
                    <a href="{{ route('reports.customer-sales-invoice-aging.index') }}" class="btn btn-outline-primary" data-testid="cash-flow-customer-aging-link">تقرير أعمار ذمم العملاء</a>
                    <a href="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="btn btn-outline-primary" data-testid="cash-flow-supplier-aging-link">تقرير أعمار ذمم الموردين</a>
                    <a href="{{ route('reports.receivable-payable-aging-dashboard.index') }}" class="btn btn-outline-primary" data-testid="cash-flow-aging-dashboard-link">لوحة أعمار الذمم</a>
                </div>
            </div>
        </div>
    </div>
@endsection
