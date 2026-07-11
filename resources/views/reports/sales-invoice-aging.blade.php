<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير أعمار ذمم فواتير المبيعات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background:#f6f1eb; color:#2f2723; margin:0; padding:24px; }
        .container { max-width:1180px; margin:0 auto; }
        .card { background:#fff; border:1px solid #e7dcd2; border-radius:18px; padding:22px; margin-bottom:18px; box-shadow:0 10px 28px rgba(69,42,23,.06); }
        .header { display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap; align-items:flex-start; }
        h1, h2 { margin-top:0; }
        .muted { color:#7a6d66; }
        .grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:14px; }
        .metric { border:1px solid #e7dcd2; border-radius:14px; padding:16px; background:#fbf8f5; }
        .metric-label { color:#7a6d66; font-size:13px; margin-bottom:8px; font-weight:700; }
        .metric-value { font-size:22px; font-weight:800; }
        .metric-sub { color:#7a6d66; margin-top:6px; font-size:13px; }
        .table-wrap { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; min-width:980px; }
        th, td { text-align:right; padding:12px; border-bottom:1px solid #e7dcd2; font-size:14px; }
        th { background:#fbf8f5; color:#7a6d66; }
        .btn { display:inline-block; background:#8b5e3c; color:#fff; border-radius:12px; padding:10px 14px; text-decoration:none; font-weight:700; }
        .btn.secondary { background:#eee4dc; color:#5d3b25; }
        .badge { display:inline-block; border-radius:999px; padding:5px 10px; background:#eee4dc; color:#5d3b25; font-size:12px; font-weight:700; }
        @media (max-width:900px) { .grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="sales-invoice-aging-report-page">
    <div class="card">
        <div class="header">
            <div>
                <h1>تقرير أعمار ذمم فواتير المبيعات</h1>
                <div class="muted">يعرض توزيع الفواتير المفتوحة حسب تاريخ الاستحقاق حتى تاريخ {{ $today }}.</div>
            </div>

            <div>
                <a class="btn secondary" href="{{ route('reports.index') }}" data-testid="sales-invoice-aging-report-back-link">رجوع للتقارير</a>
                <a class="btn secondary"
                   href="{{ route('reports.sales-invoice-aging.export', request()->only(['customer_id', 'payment_status', 'aging_bucket'])) }}"
                   data-testid="sales-invoice-aging-report-export-link">تصدير CSV</a>
                <a class="btn" href="{{ route('sales-invoices.index', ['collection_status' => 'outstanding']) }}" data-testid="sales-invoice-aging-report-outstanding-link">الفواتير ذات المتبقي</a>
            </div>
        </div>
    </div>


        @if (session('status'))
            <div class="alert alert-success" data-testid="sales-invoice-aging-status">
                {{ session('status') }}
            </div>
        @endif

    <div class="card" data-testid="sales-invoice-aging-report-filters-card">
        <h2>فلاتر التقرير</h2>

        <form method="GET" action="{{ route('reports.sales-invoice-aging.index') }}">
            <div class="grid">
                <div class="metric">
                    <label class="metric-label" for="sales_invoice_aging_customer_filter">العميل</label>
                    <select id="sales_invoice_aging_customer_filter"
                            name="customer_id"
                            data-testid="sales-invoice-aging-customer-filter"
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
                    <label class="metric-label" for="sales_invoice_aging_payment_status_filter">حالة الدفع</label>
                    <select id="sales_invoice_aging_payment_status_filter"
                            name="payment_status"
                            data-testid="sales-invoice-aging-payment-status-filter"
                            style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
                        <option value="">كل الحالات</option>
                        <option value="unpaid" @selected((string) $paymentStatusFilter === 'unpaid')>غير مدفوعة</option>
                        <option value="partial" @selected((string) $paymentStatusFilter === 'partial')>مدفوعة جزئيًا</option>
                        <option value="paid" @selected((string) $paymentStatusFilter === 'paid')>مدفوعة بالكامل</option>
                    </select>
                </div>

                <div class="metric">
                    <label class="metric-label" for="sales_invoice_aging_bucket_filter">شريحة العمر</label>
                    <select id="sales_invoice_aging_bucket_filter"
                            name="aging_bucket"
                            data-testid="sales-invoice-aging-bucket-filter"
                            style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;">
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
                    <button type="submit" class="btn" data-testid="sales-invoice-aging-apply-filters-button">تطبيق الفلتر</button>
                    <a href="{{ route('reports.sales-invoice-aging.index', ['reset_filters' => 1]) }}"
                       class="btn secondary"
                       data-testid="sales-invoice-aging-reset-filters-link"
                       style="margin-top:8px;">إعادة ضبط</a>
                </div>
            </div>
        </form>
    </div>



    @php
        $salesInvoiceAgingSavedViews = $savedViews ?? collect();
    @endphp

    <div class="card" data-testid="sales-invoice-aging-saved-views-selector">
        @include('reports.partials.saved-view-section', [
            'savedViews' => $salesInvoiceAgingSavedViews,
            'routeName' => 'reports.sales-invoice-aging.index',
            'emptyTestId' => 'sales-invoice-aging-saved-views-empty',
            'listTestId' => 'sales-invoice-aging-saved-views-list',
            'itemTestId' => 'sales-invoice-aging-saved-view-item',
            'openLinkTestId' => 'sales-invoice-aging-saved-view-open-link',
            'activeBadgeTestId' => 'sales-invoice-aging-saved-view-active-badge',
            'defaultBadgeTestId' => 'sales-invoice-aging-saved-view-default-badge',
            'manageLinkTestId' => 'sales-invoice-aging-manage-saved-views-link',
        ])
    </div>

    <div class="card" data-testid="sales-invoice-aging-save-view-card">
        <h2>حفظ عرض التقرير</h2>

        <form method="POST" action="{{ route('reports.sales-invoice-aging.saved-views.store') }}" data-testid="sales-invoice-aging-save-view-form">
            @csrf

            <input type="hidden" name="customer_id" value="{{ $customerFilter }}">
            <input type="hidden" name="payment_status" value="{{ $paymentStatusFilter }}">
            <input type="hidden" name="aging_bucket" value="{{ $agingBucketFilter }}">

            <div class="grid">
                <div class="metric">
                    <label class="metric-label" for="sales_invoice_aging_saved_view_name">اسم العرض المحفوظ</label>
                    <input id="sales_invoice_aging_saved_view_name"
                           type="text"
                           name="name"
                           placeholder="مثال: متابعة التحصيل الجزئي"
                           required
                           maxlength="120"
                           style="width:100%;padding:10px;border:1px solid #e7dcd2;border-radius:10px;"
                           data-testid="sales-invoice-aging-saved-view-name-input">
                </div>

                <div class="metric">
                    <div class="metric-label">خيارات العرض</div>
                    <label>
                        <input type="checkbox" name="is_default" value="1" data-testid="sales-invoice-aging-saved-view-default-checkbox">
                        تعيين كعرض افتراضي لهذا التقرير
                    </label>
                </div>

                <div class="metric">
                    <div class="metric-label">الإجراء</div>
                    <button type="submit" class="btn" data-testid="sales-invoice-aging-save-view-button">
                        حفظ العرض
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card" data-testid="sales-invoice-aging-total-card">
        <h2>الإجمالي العام</h2>
        <div class="grid">
            <div class="metric">
                <div class="metric-label">عدد الفواتير المفتوحة</div>
                <div class="metric-value" data-testid="sales-invoice-aging-total-count">{{ $totalCount }}</div>
            </div>

            <div class="metric">
                <div class="metric-label">إجمالي المتبقي</div>
                <div class="metric-value" data-testid="sales-invoice-aging-total-amount">{{ number_format($totalOutstanding, 2) }} ريال</div>
            </div>

            <div class="metric">
                <div class="metric-label">تاريخ التقرير</div>
                <div class="metric-value" style="font-size:18px;">{{ $today }}</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="sales-invoice-aging-summary-card">
        <h2>شرائح الأعمار</h2>

        <div class="grid">
            @foreach ($summary as $key => $bucket)
                <div class="metric" data-testid="sales-invoice-aging-bucket-{{ $key }}">
                    <div class="metric-label">{{ $bucket['label'] }}</div>
                    <div class="metric-value">{{ number_format($bucket['total'], 2) }} ريال</div>
                    <div class="metric-sub">عدد الفواتير: {{ $bucket['count'] }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="card" data-testid="sales-invoice-aging-invoices-card">
        <h2>الفواتير المفتوحة حسب الأقدمية</h2>

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
                        <th>الشريحة</th>
                        <th>عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        @php
                            $bucketLabel = 'بدون تاريخ استحقاق';

                            if ($invoice->due_at) {
                                if ($invoice->due_at->toDateString() >= $today) {
                                    $bucketLabel = 'غير مستحقة بعد';
                                } else {
                                    $daysOverdue = $invoice->due_at->diffInDays(now());

                                    if ($daysOverdue <= 30) {
                                        $bucketLabel = 'متأخرة 1 إلى 30 يوم';
                                    } elseif ($daysOverdue <= 60) {
                                        $bucketLabel = 'متأخرة 31 إلى 60 يوم';
                                    } elseif ($daysOverdue <= 90) {
                                        $bucketLabel = 'متأخرة 61 إلى 90 يوم';
                                    } else {
                                        $bucketLabel = 'أكثر من 90 يوم';
                                    }
                                }
                            }
                        @endphp

                        <tr data-testid="sales-invoice-aging-row">
                            <td dir="ltr">{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->customer?->name ?: '-' }}</td>
                            <td>{{ $invoice->displayPaymentStatus() }}</td>
                            <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->paid_amount, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                            <td>{{ $invoice->due_at?->format('Y-m-d') ?: '-' }}</td>
                            <td><span class="badge">{{ $bucketLabel }}</span></td>
                            <td><a href="{{ route('sales-invoices.show', $invoice) }}">التفاصيل</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" data-testid="sales-invoice-aging-empty">لا توجد فواتير مفتوحة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
