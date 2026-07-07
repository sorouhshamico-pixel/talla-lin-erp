@php($financialDashboardSummaryService = app(\App\Services\FinancialDashboardSummaryService::class))
@php($financialDashboardSummary = $financialDashboardSummaryService->summary(request()))
@php($topOverdueCustomers = $financialDashboardSummaryService->topOverdueCustomers(request(), 5))
@php($topOverdueSuppliers = $financialDashboardSummaryService->topOverdueSuppliers(request(), 5))

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

        <div class="card" data-testid="main-dashboard-top-overdue-customers" style="margin-top: 20px;">
            <div class="card-body">
                <div class="page-header">
                    <div>
                        <h3>أكبر العملاء المتأخرين</h3>
                        <p>أعلى العملاء حسب إجمالي فواتير البيع المتأخرة المفتوحة.</p>
                    </div>

                    <a href="{{ route('dashboard.top-overdue-customers.export') }}" class="btn btn-outline-secondary" data-testid="main-dashboard-top-overdue-customers-export-link">تصدير العملاء CSV</a>
                    <a href="{{ route('reports.customer-sales-invoice-aging.drilldown', ['aging_bucket' => 'overdue_more_than_90']) }}" class="btn btn-outline-primary" data-testid="main-dashboard-top-overdue-customers-more-link">عرض تفاصيل المتأخرات</a>
                </div>

                @if (empty($topOverdueCustomers))
                    <div class="empty-state" data-testid="main-dashboard-top-overdue-customers-empty">
                        لا توجد فواتير عملاء متأخرة حاليًا.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="main-dashboard-top-overdue-customers-table">
                            <thead>
                                <tr>
                                    <th>العميل</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المتأخر</th>
                                    <th>أقدم استحقاق</th>
                                    <th>أقصى تأخير</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topOverdueCustomers as $row)
                                    <tr>
                                        <td>{{ $row['customer_name'] }}</td>
                                        <td>{{ $row['invoice_count'] }}</td>
                                        <td>{{ number_format((float) $row['overdue_total'], 2) }} ريال</td>
                                        <td>{{ $row['oldest_due_at'] ?? '' }}</td>
                                        <td>{{ $row['max_days_overdue'] === null ? '' : $row['max_days_overdue'] . ' يوم' }}</td>
                                        <td>
                                            @if ($row['customer_id'])
                                                <a href="{{ route('reports.customer-sales-invoice-aging.drilldown', ['customer_id' => $row['customer_id']]) }}" data-testid="main-dashboard-top-overdue-customer-link-{{ $row['customer_id'] }}">
                                                    عرض الفواتير
                                                </a>
                                            @else
                                                غير متاح
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
        <div class="card" data-testid="main-dashboard-top-overdue-suppliers" style="margin-top: 20px;">
            <div class="card-body">
                <div class="page-header">
                    <div>
                        <h3>أكبر الموردين المتأخرين</h3>
                        <p>أعلى الموردين حسب إجمالي فواتير الشراء المتأخرة المفتوحة.</p>
                    </div>

                    <a href="{{ route('dashboard.top-overdue-suppliers.export') }}" class="btn btn-outline-secondary" data-testid="main-dashboard-top-overdue-suppliers-export-link">تصدير الموردين CSV</a>
                    <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', ['aging_bucket' => 'overdue_more_than_90']) }}" class="btn btn-outline-primary" data-testid="main-dashboard-top-overdue-suppliers-more-link">عرض تفاصيل المتأخرات</a>
                </div>

                @if (empty($topOverdueSuppliers))
                    <div class="empty-state" data-testid="main-dashboard-top-overdue-suppliers-empty">
                        لا توجد فواتير موردين متأخرة حاليًا.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table" data-testid="main-dashboard-top-overdue-suppliers-table">
                            <thead>
                                <tr>
                                    <th>المورد</th>
                                    <th>عدد الفواتير</th>
                                    <th>إجمالي المتأخر</th>
                                    <th>أقدم استحقاق</th>
                                    <th>أقصى تأخير</th>
                                    <th>التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($topOverdueSuppliers as $row)
                                    <tr>
                                        <td>{{ $row['supplier_name'] }}</td>
                                        <td>{{ $row['invoice_count'] }}</td>
                                        <td>{{ number_format((float) $row['overdue_total'], 2) }} ريال</td>
                                        <td>{{ $row['oldest_due_at'] ?? '' }}</td>
                                        <td>{{ $row['max_days_overdue'] === null ? '' : $row['max_days_overdue'] . ' يوم' }}</td>
                                        <td>
                                            @if ($row['supplier_id'])
                                                <a href="{{ route('reports.supplier-purchase-invoice-aging.drilldown', ['supplier_id' => $row['supplier_id']]) }}" data-testid="main-dashboard-top-overdue-supplier-link-{{ $row['supplier_id'] }}">
                                                    عرض الفواتير
                                                </a>
                                            @else
                                                غير متاح
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
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
