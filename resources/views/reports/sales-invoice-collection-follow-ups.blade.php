<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير متابعات تحصيل فواتير المبيعات</title>
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
        table { width:100%; border-collapse:collapse; min-width:980px; }
        th, td { text-align:right; padding:12px; border-bottom:1px solid #e7dcd2; font-size:14px; vertical-align:top; }
        th { background:#fbf8f5; color:#7a6d66; }
        .btn { display:inline-block; background:#8b5e3c; color:#fff; border-radius:12px; padding:10px 14px; text-decoration:none; font-weight:700; }
        .btn.secondary { background:#eee4dc; color:#5d3b25; }
        .badge { display:inline-block; border-radius:999px; padding:5px 10px; background:#eee4dc; color:#5d3b25; font-size:12px; font-weight:700; }
        @media (max-width:900px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="sales-invoice-collection-follow-up-report-page">
    <div class="card">
        <div class="header">
            <div>
                <h1>تقرير متابعات تحصيل فواتير المبيعات</h1>
                <div class="muted">
                    يعرض ملاحظات التحصيل التي حان موعد متابعتها حتى تاريخ {{ $today }} مع استبعاد الفواتير المسددة بالكامل.
                </div>
            </div>

            <div>
                <a class="btn secondary" href="{{ route('reports.index') }}" data-testid="collection-follow-up-report-back-link">رجوع للتقارير</a>
                <a class="btn secondary"
                   href="{{ route('reports.sales-invoice-collection-follow-ups.export', request()->only(['customer_id', 'follow_up_from', 'follow_up_to'])) }}"
                   data-testid="collection-follow-up-report-export-link">تصدير CSV</a>
                <a class="btn" href="{{ route('reports.sales-invoice-collections.index') }}" data-testid="collection-follow-up-report-collection-report-link">تقرير التحصيل</a>
            </div>
        </div>
    </div>

    <div class="card" data-testid="collection-follow-up-report-filters-card">
        <h2>فلاتر التقرير</h2>

        <form method="GET" action="{{ route('reports.sales-invoice-collection-follow-ups.index') }}">
            <div class="grid">
                <div class="metric">
                    <label class="metric-label" for="collection_follow_up_customer_filter">العميل</label>
                    <select id="collection_follow_up_customer_filter"
                            name="customer_id"
                            data-testid="collection-follow-up-customer-filter"
                            style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
                        <option value="">كل العملاء</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $customerFilter === (string) $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="metric">
                    <label class="metric-label" for="collection_follow_up_from_filter">من تاريخ متابعة</label>
                    <input id="collection_follow_up_from_filter"
                           type="date"
                           name="follow_up_from"
                           value="{{ $followUpFromFilter }}"
                           data-testid="collection-follow-up-from-filter"
                           style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
                </div>

                <div class="metric">
                    <label class="metric-label" for="collection_follow_up_to_filter">إلى تاريخ متابعة</label>
                    <input id="collection_follow_up_to_filter"
                           type="date"
                           name="follow_up_to"
                           value="{{ $followUpToFilter }}"
                           data-testid="collection-follow-up-to-filter"
                           style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
                </div>

                <div class="metric">
                    <div class="metric-label">الإجراء</div>
                    <button type="submit" class="btn" data-testid="collection-follow-up-apply-filters-button">تطبيق الفلتر</button>
                    <a href="{{ route('reports.sales-invoice-collection-follow-ups.index') }}"
                       class="btn secondary"
                       data-testid="collection-follow-up-reset-filters-link"
                       style="margin-top:8px;">إعادة ضبط</a>
                </div>
            </div>
        </form>
    </div>

    <div class="card" data-testid="collection-follow-up-summary-card">
        <h2>ملخص المتابعات</h2>

        <div class="grid">
            <div class="metric">
                <div class="metric-label">متابعات مستحقة</div>
                <div class="metric-value" data-testid="collection-follow-up-due-count">{{ $summary['due_notes_count'] }}</div>
                <div class="metric-sub">اليوم أو قبل اليوم</div>
            </div>

            <div class="metric">
                <div class="metric-label">متابعات قادمة</div>
                <div class="metric-value" data-testid="collection-follow-up-upcoming-count">{{ $summary['upcoming_notes_count'] }}</div>
                <div class="metric-sub">بعد تاريخ اليوم</div>
            </div>

            <div class="metric">
                <div class="metric-label">فواتير مرتبطة</div>
                <div class="metric-value" data-testid="collection-follow-up-invoices-count">{{ $summary['due_invoices_count'] }}</div>
                <div class="metric-sub">فواتير تحتاج إجراء</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي المتبقي</div>
                <div class="metric-value" data-testid="collection-follow-up-remaining-total">{{ number_format($summary['due_remaining_total'], 2) }} ريال</div>
                <div class="metric-sub">للفواتير المستحقة للمتابعة</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="collection-follow-up-notes-card">
        <h2>ملاحظات تحتاج متابعة</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>تاريخ المتابعة</th>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>المتبقي</th>
                        <th>تاريخ الاستحقاق</th>
                        <th>الملاحظة</th>
                        <th>المستخدم</th>
                        <th>عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($dueNotes as $note)
                        <tr data-testid="collection-follow-up-note-row">
                            <td>{{ $note->follow_up_at?->format('Y-m-d') ?: '-' }}</td>
                            <td dir="ltr">{{ $note->salesInvoice?->invoice_number ?: '-' }}</td>
                            <td>{{ $note->salesInvoice?->customer?->name ?: '-' }}</td>
                            <td>{{ number_format((float) ($note->salesInvoice?->remaining_amount ?? 0), 2) }} ريال</td>
                            <td>{{ $note->salesInvoice?->due_at?->format('Y-m-d') ?: '-' }}</td>
                            <td>{{ $note->note }}</td>
                            <td>{{ $note->user?->name ?: '-' }}</td>
                            <td>
                                @if ($note->salesInvoice)
                                    <a href="{{ route('sales-invoices.show', $note->salesInvoice) }}">التفاصيل</a>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" data-testid="collection-follow-up-notes-empty">لا توجد متابعات تحصيل مستحقة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
