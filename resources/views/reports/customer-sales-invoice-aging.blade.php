<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير أعمار ذمم العملاء</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f6f1eb; color:#2f2723; margin:0; padding:24px; }
        .container { max-width:1280px; margin:0 auto; }
        .card { background:#fff; border:1px solid #e7dcd2; border-radius:18px; padding:22px; margin-bottom:18px; box-shadow:0 10px 28px rgba(69,42,23,.06); }
        .header { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-start; }
        h1, h2 { margin-top:0; }
        .muted { color:#7a6d66; }
        .grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .metric { border:1px solid #e7dcd2; border-radius:14px; padding:16px; background:#fbf8f5; }
        .metric-label { color:#7a6d66; font-size:13px; margin-bottom:8px; font-weight:700; }
        .metric-value { font-size:22px; font-weight:800; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; min-width:1180px; }
        th, td { text-align:right; padding:12px; border-bottom:1px solid #e7dcd2; font-size:14px; }
        th { background:#fbf8f5; color:#7a6d66; }
        .btn { display:inline-block; background:#8b5e3c; color:#fff; border-radius:12px; padding:10px 14px; text-decoration:none; font-weight:700; }
        .btn.secondary { background:#eee4dc; color:#5d3b25; }
        @media (max-width:900px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="customer-sales-invoice-aging-report-page">
    <div class="card">
        <div class="header">
            <div>
                <h1>تقرير أعمار ذمم العملاء</h1>
                <div class="muted">يعرض توزيع الذمم المفتوحة حسب العميل وشرائح التأخير حتى تاريخ {{ $today }}.</div>
            </div>

            <div>
                <a class="btn secondary" href="{{ route('reports.index') }}" data-testid="customer-aging-report-back-link">رجوع للتقارير</a>
<a href="{{ route('reports.customer-sales-invoice-aging.print', request()->only(['customer_id', 'aging_bucket'])) }}" class="btn btn-outline-secondary" data-testid="customer-aging-print-link">طباعة التقرير</a>
                        <a href="{{ route('reports.customer-sales-invoice-aging.drilldown', request()->only(['customer_id', 'aging_bucket'])) }}" class="btn btn-outline-primary" data-testid="customer-aging-drilldown-link">تفاصيل الفواتير المفتوحة</a>
<a href="{{ route('reports.customer-sales-invoice-aging.export', request()->only(['customer_id', 'aging_bucket'])) }}" class="btn btn-outline-primary" data-testid="customer-aging-export-button">تصدير CSV</a>
                <a class="btn" href="{{ route('reports.sales-invoice-aging.index') }}" data-testid="customer-aging-report-aging-link">تقرير أعمار الفواتير</a>
            </div>
        </div>
    </div>

    <div class="card" data-testid="customer-aging-filters-card">
        <h2>فلاتر التقرير</h2>

        <form method="GET" action="{{ route('reports.customer-sales-invoice-aging.index') }}">
            <div class="grid">
                <div class="metric">
                    <label class="metric-label">العميل</label>
                    <select name="customer_id" data-testid="customer-aging-customer-filter" style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
                        <option value="">كل العملاء</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $customerFilter === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="metric">
                    <label class="metric-label">شريحة العمر</label>
                    <select name="aging_bucket" data-testid="customer-aging-bucket-filter" style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
                        <option value="">كل الشرائح</option>
                        <option value="not_due" @selected((string) $agingBucketFilter === 'not_due')>غير مستحقة بعد</option>
                        <option value="overdue_1_30" @selected((string) $agingBucketFilter === 'overdue_1_30')>متأخرة 1 إلى 30 يوم</option>
                        <option value="overdue_31_60" @selected((string) $agingBucketFilter === 'overdue_31_60')>متأخرة 31 إلى 60 يوم</option>
                        <option value="overdue_61_90" @selected((string) $agingBucketFilter === 'overdue_61_90')>متأخرة 61 إلى 90 يوم</option>
                        <option value="overdue_more_than_90" @selected((string) $agingBucketFilter === 'overdue_more_than_90')>أكثر من 90 يوم</option>
                        <option value="without_due_date" @selected((string) $agingBucketFilter === 'without_due_date')>بدون تاريخ استحقاق</option>
                    </select>
                </div>

                <div class="metric">
                    <div class="metric-label">الإجراء</div>
                    <button type="submit" class="btn" data-testid="customer-aging-apply-filters-button">تطبيق الفلتر</button>
                    <a href="{{ route('reports.customer-sales-invoice-aging.index') }}" class="btn secondary" data-testid="customer-aging-reset-filters-link" style="margin-top:8px;">إعادة ضبط</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card" data-testid="customer-aging-summary-card">
        <h2>ملخص عام</h2>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">عدد العملاء</div>
                <div class="metric-value" data-testid="customer-aging-customers-count">{{ $summary['customers_count'] }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">عدد الفواتير المفتوحة</div>
                <div class="metric-value" data-testid="customer-aging-invoices-count">{{ $summary['invoice_count'] }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي الذمم المفتوحة</div>
                <div class="metric-value" data-testid="customer-aging-remaining-total">{{ number_format($summary['remaining_total'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي المتأخر</div>
                <div class="metric-value" data-testid="customer-aging-overdue-total">{{ number_format($summary['overdue_total'], 2) }} ريال</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="customer-aging-table-card">
        <h2>أعمار الذمم حسب العميل</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>العميل</th>
                        <th>عدد الفواتير</th>
                        <th>إجمالي المتبقي</th>
                        <th>غير مستحقة بعد</th>
                        <th>متأخرة 1 إلى 30</th>
                        <th>متأخرة 31 إلى 60</th>
                        <th>متأخرة 61 إلى 90</th>
                        <th>أكثر من 90</th>
                        <th>بدون تاريخ استحقاق</th>
                        <th>أقدم استحقاق</th>
                        <th>عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr data-testid="customer-aging-row">
                            <td>{{ $row['customer']?->name ?: '-' }}</td>
                            <td>{{ $row['invoice_count'] }}</td>
                            <td>{{ number_format($row['remaining_total'], 2) }} ريال</td>
                            <td>{{ number_format($row['not_due_total'], 2) }} ريال</td>
                            <td>{{ number_format($row['overdue_1_30_total'], 2) }} ريال</td>
                            <td>{{ number_format($row['overdue_31_60_total'], 2) }} ريال</td>
                            <td>{{ number_format($row['overdue_61_90_total'], 2) }} ريال</td>
                            <td>{{ number_format($row['overdue_more_than_90_total'], 2) }} ريال</td>
                            <td>{{ number_format($row['without_due_date_total'], 2) }} ريال</td>
                            <td>{{ $row['oldest_due_at']?->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                @if ($row['customer'])
                                    <a href="{{ route('sales-invoices.index', ['customer_id' => $row['customer']->id, 'collection_status' => 'outstanding']) }}">الفواتير</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" data-testid="customer-aging-empty">لا توجد ذمم مفتوحة للعملاء.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
