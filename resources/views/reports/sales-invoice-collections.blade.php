<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير تحصيل فواتير المبيعات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f6f1eb; color:#2f2723; margin:0; padding:24px; }
        .container { max-width:1180px; margin:0 auto; }
        .card { background:#fff; border:1px solid #e7dcd2; border-radius:18px; padding:22px; margin-bottom:18px; box-shadow:0 10px 28px rgba(69,42,23,.06); }
        .header { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-start; }
        h1, h2 { margin-top:0; }
        .muted { color:#7a6d66; }
        .grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; }
        .metric { border:1px solid #e7dcd2; border-radius:14px; padding:16px; background:#fbf8f5; }
        .metric-label { color:#7a6d66; font-size:13px; margin-bottom:8px; font-weight:700; }
        .metric-value { font-size:22px; font-weight:800; }
        .metric-sub { color:#7a6d66; margin-top:6px; font-size:13px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; min-width:900px; }
        th, td { text-align:right; padding:12px; border-bottom:1px solid #e7dcd2; font-size:14px; }
        th { background:#fbf8f5; color:#7a6d66; }
        .btn { display:inline-block; background:#8b5e3c; color:#fff; border-radius:12px; padding:10px 14px; text-decoration:none; font-weight:700; }
        .btn.secondary { background:#eee4dc; color:#5d3b25; }
        .badge { display:inline-block; border-radius:999px; padding:5px 10px; background:#eee4dc; color:#5d3b25; font-size:12px; font-weight:700; }
        @media (max-width:900px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="sales-invoice-collection-report-page">
    @if (session('status'))
        <div class="card" data-testid="sales-invoice-collections-status">
            {{ session('status') }}
        </div>
    @endif

    <div class="card">
        <div class="header">
            <div>
                <h1>تقرير تحصيل فواتير المبيعات</h1>
                <div class="muted">ملخص الفواتير التي لا يزال عليها مبلغ متبقٍ وتحتاج متابعة تحصيل.</div>
            </div>

            <div>
                <a class="btn secondary" href="{{ route('reports.index') }}" data-testid="sales-invoice-collection-report-back-link">رجوع للتقارير</a>
                <a class="btn" href="{{ route('sales-invoices.index', ['collection_status' => 'overdue']) }}" data-testid="sales-invoice-collection-report-overdue-link">عرض المتأخرة</a>
            </div>
        </div>
    </div>

    @include('reports.partials.sales-invoice-collections-saved-view-controls-config')

    <div class="card" data-testid="sales-invoice-collection-summary-card">
        <h2>ملخص التحصيل</h2>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">كل الفواتير ذات المتبقي</div>
                <div class="metric-value" data-testid="collection-outstanding-count">{{ $summary['outstanding_count'] }}</div>
                <div class="metric-sub" data-testid="collection-outstanding-total">{{ number_format($summary['outstanding_total'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">الفواتير المتأخرة</div>
                <div class="metric-value" data-testid="collection-overdue-count">{{ $summary['overdue_count'] }}</div>
                <div class="metric-sub" data-testid="collection-overdue-total">{{ number_format($summary['overdue_total'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">غير مدفوعة</div>
                <div class="metric-value" data-testid="collection-unpaid-count">{{ $summary['unpaid_count'] }}</div>
                <div class="metric-sub" data-testid="collection-unpaid-total">{{ number_format($summary['unpaid_total'], 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">مدفوعة جزئيًا</div>
                <div class="metric-value" data-testid="collection-partial-count">{{ $summary['partial_count'] }}</div>
                <div class="metric-sub" data-testid="collection-partial-total">{{ number_format($summary['partial_total'], 2) }} ريال</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="sales-invoice-collection-invoices-card">
        <h2>فواتير تحتاج متابعة</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>حالة الدفع</th>
                        <th>الإجمالي</th>
                        <th>المدفوع</th>
                        <th>المتبقي</th>
                        <th>تاريخ الاستحقاق</th>
                        <th>الحالة</th>
                        <th>عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr data-testid="collection-invoice-row">
                            <td dir="ltr">{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->customer?->name ?: '-' }}</td>
                            <td>{{ $invoice->displayPaymentStatus() }}</td>
                            <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->paid_amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                            <td>{{ $invoice->due_at?->format('Y-m-d') ?: '-' }}</td>
                            <td>
                                @if ($invoice->due_at && $invoice->due_at->toDateString() < now()->toDateString())
                                    <span class="badge">متأخرة</span>
                                @else
                                    <span class="badge">قيد المتابعة</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('sales-invoices.show', $invoice) }}">التفاصيل</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" data-testid="collection-invoices-empty">لا توجد فواتير تحتاج متابعة تحصيل.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
