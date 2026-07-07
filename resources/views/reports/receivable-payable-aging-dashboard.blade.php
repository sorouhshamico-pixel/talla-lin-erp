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

                <div class="report-actions">
                    <a href="{{ route('reports.customer-sales-invoice-aging.index') }}" class="btn btn-outline-primary" data-testid="aging-dashboard-customer-report-link">تقرير أعمار ذمم العملاء</a>
                    <a href="{{ route('reports.supplier-purchase-invoice-aging.index') }}" class="btn btn-outline-primary" data-testid="aging-dashboard-supplier-report-link">تقرير أعمار ذمم الموردين</a>
                </div>
            </div>
        </div>
    </div>
@endsection
