@php($financialDashboardSummary = app(\App\Services\FinancialDashboardSummaryService::class)->summary(request()))

<div class="card" data-testid="main-dashboard-financial-summary">
    <div class="card-body">
        <div class="page-header">
            <div>
                <h2>الملخص المالي السريع</h2>
                <p>نظرة مختصرة على ذمم العملاء، التزامات الموردين، وصافي التدفق النقدي المتوقع.</p>
            </div>
        </div>

        <div class="summary-grid" data-testid="main-dashboard-financial-cards">
            <div class="summary-card">
                <span>ذمم العملاء المفتوحة</span>
                <strong>{{ number_format((float) $financialDashboardSummary['expected_inflows'], 2) }} ريال</strong>
            </div>

            <div class="summary-card">
                <span>التزامات الموردين المفتوحة</span>
                <strong>{{ number_format((float) $financialDashboardSummary['expected_outflows'], 2) }} ريال</strong>
            </div>

            <div class="summary-card">
                <span>صافي التدفق النقدي المتوقع</span>
                <strong>{{ number_format((float) $financialDashboardSummary['net_expected_cash'], 2) }} ريال</strong>
            </div>

            <div class="summary-card">
                <span>حالة التدفق النقدي</span>
                <strong>{{ $financialDashboardSummary['position_label'] }}</strong>
            </div>
        </div>

        <div class="summary-grid" data-testid="main-dashboard-aging-cards">
            <div class="summary-card">
                <span>فواتير العملاء المفتوحة</span>
                <strong>{{ $financialDashboardSummary['customer_open_invoice_count'] }}</strong>
            </div>

            <div class="summary-card">
                <span>فواتير الموردين المفتوحة</span>
                <strong>{{ $financialDashboardSummary['supplier_open_invoice_count'] }}</strong>
            </div>

            <div class="summary-card">
                <span>متأخرات العملاء</span>
                <strong>{{ number_format((float) $financialDashboardSummary['overdue_inflows'], 2) }} ريال</strong>
            </div>

            <div class="summary-card">
                <span>متأخرات الموردين</span>
                <strong>{{ number_format((float) $financialDashboardSummary['overdue_outflows'], 2) }} ريال</strong>
            </div>
        </div>

        <div class="summary-grid" data-testid="main-dashboard-financial-risk-cards">
            <div class="summary-card">
                <span>صافي الضغط النقدي المتأخر</span>
                <strong>{{ number_format((float) $financialDashboardSummary['net_overdue_pressure'], 2) }} ريال</strong>
            </div>

            <div class="summary-card">
                <span>نسبة تغطية الالتزامات</span>
                <strong>{{ $financialDashboardSummary['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $financialDashboardSummary['cash_coverage_ratio'], 2) . '%' }}</strong>
            </div>

            <div class="summary-card">
                <span>حالة التغطية النقدية</span>
                <strong>{{ $financialDashboardSummary['cash_coverage_label'] }}</strong>
            </div>

            <div class="summary-card">
                <span>مؤشر المتابعة المالية</span>
                <strong>{{ $financialDashboardSummary['risk_label'] }}</strong>
            </div>
        </div>

        <div class="report-actions">
            <a href="{{ route('reports.cash-flow-dashboard.index') }}" class="btn btn-outline-primary" data-testid="main-dashboard-cash-flow-link">لوحة التدفق النقدي</a>
            <a href="{{ route('reports.receivable-payable-aging-dashboard.index') }}" class="btn btn-outline-primary" data-testid="main-dashboard-aging-link">لوحة أعمار الذمم</a>
            <a href="{{ route('reports.customer-sales-invoice-aging.drilldown') }}" class="btn btn-outline-primary" data-testid="main-dashboard-customer-drilldown-link">تفاصيل فواتير العملاء</a>
            <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown') }}" class="btn btn-outline-primary" data-testid="main-dashboard-supplier-drilldown-link">تفاصيل فواتير الموردين</a>
            <a href="{{ route('dashboard.financial-summary.print') }}" class="btn btn-outline-secondary" data-testid="main-dashboard-financial-print-link">طباعة الملخص</a>
            <a href="{{ route('dashboard.financial-summary.export') }}" class="btn btn-outline-secondary" data-testid="main-dashboard-financial-export-link">تصدير الملخص CSV</a>
            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary" data-testid="main-dashboard-reports-link">مركز التقارير</a>
        </div>
    </div>
</div>
