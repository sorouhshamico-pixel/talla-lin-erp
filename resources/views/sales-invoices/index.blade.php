@extends('layouts.admin', [
    'title' => 'فواتير البيع | طلة لين ERP',
    'header' => 'فواتير البيع'
])

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">فواتير البيع</h1>
            <div class="muted">
                عرض فواتير البيع وإجمالياتها وحالة السداد.
            </div>
        </div>

        <div>
            <a href="{{ route('sales-invoices.create') }}"
               style="display:inline-block;background:#8b5e3c;color:#fff;padding:11px 16px;border-radius:12px;font-weight:700;">
                فاتورة جديدة
            </a>
        </div>
    </div>

    <div class="card" data-testid="sales-invoice-filters-card" style="margin-bottom:20px;">
        <form method="GET" action="{{ route('sales-invoices.index') }}">
            <div class="grid" style="align-items:end;">
                <div class="field">
                    <label for="sales_invoice_customer_filter" class="label">العميل</label>
                    <select id="sales_invoice_customer_filter" name="customer_id" data-testid="sales-invoice-customer-filter">
                        <option value="">كل العملاء</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $customerFilter === (string) $customer->id)>
                                {{ $customer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="sales_invoice_payment_status_filter" class="label">حالة الدفع</label>
                    <select id="sales_invoice_payment_status_filter" name="payment_status" data-testid="sales-invoice-payment-status-filter">
                        <option value="">كل الحالات</option>
                        <option value="unpaid" @selected((string) $paymentStatusFilter === 'unpaid')>غير مدفوعة</option>
                        <option value="partial" @selected((string) $paymentStatusFilter === 'partial')>مدفوعة جزئيًا</option>
                        <option value="paid" @selected((string) $paymentStatusFilter === 'paid')>مدفوعة بالكامل</option>
                    </select>
                </div>

                <div class="field">
                    <label for="sales_invoice_collection_status_filter" class="label">حالة التحصيل</label>
                    <select id="sales_invoice_collection_status_filter" name="collection_status" data-testid="sales-invoice-collection-status-filter">
                        <option value="">كل الفواتير</option>
                        <option value="outstanding" @selected((string) $collectionStatusFilter === 'outstanding')>فواتير ذات مبالغ متبقية</option>
                        <option value="overdue" @selected((string) $collectionStatusFilter === 'overdue')>فواتير متأخرة التحصيل</option>
                    </select>
                </div>

                <div class="field">
                    <label for="sales_invoice_issued_from_filter" class="label">من تاريخ</label>
                    <input id="sales_invoice_issued_from_filter"
                           type="date"
                           name="issued_from"
                           value="{{ $issuedFromFilter }}"
                           data-testid="sales-invoice-issued-from-filter">
                </div>

                <div class="field">
                    <label for="sales_invoice_issued_to_filter" class="label">إلى تاريخ</label>
                    <input id="sales_invoice_issued_to_filter"
                           type="date"
                           name="issued_to"
                           value="{{ $issuedToFilter }}"
                           data-testid="sales-invoice-issued-to-filter">
                </div>

                <div class="field">
                    <label for="sales_invoice_due_from_filter" class="label">من تاريخ الاستحقاق</label>
                    <input id="sales_invoice_due_from_filter"
                           type="date"
                           name="due_from"
                           value="{{ $dueFromFilter }}"
                           data-testid="sales-invoice-due-from-filter">
                </div>

                <div class="field">
                    <label for="sales_invoice_due_to_filter" class="label">إلى تاريخ الاستحقاق</label>
                    <input id="sales_invoice_due_to_filter"
                           type="date"
                           name="due_to"
                           value="{{ $dueToFilter }}"
                           data-testid="sales-invoice-due-to-filter">
                </div>

                <div class="field">
                    <button type="submit" class="btn" data-testid="sales-invoice-apply-filters-button">تطبيق الفلتر</button>
                    <a href="{{ route('sales-invoices.index') }}" class="btn secondary" data-testid="sales-invoice-reset-filters-link">إعادة ضبط</a>
                </div>
            </div>
        </form>
    </div>

    @php
        if (! isset($salesInvoiceSummary)) {
            $salesInvoiceSummary = [
                'count' => $invoices->count(),
                'grand_total' => round((float) $invoices->sum('grand_total'), 2),
                'paid_amount' => round((float) $invoices->sum('paid_amount'), 2),
                'remaining_amount' => round((float) $invoices->sum('remaining_amount'), 2),
                'outstanding_count' => $invoices->where('remaining_amount', '>', 0)->count(),
                'paid_count' => $invoices->where('payment_status', 'paid')->count(),
            ];
        }
    @endphp

    <div class="card" data-testid="sales-invoice-export-links-card" style="margin-bottom:20px;">
        <h2>تصدير فواتير المبيعات</h2>
        <div class="muted">تصدير نتائج الفواتير حسب الفلاتر الحالية إلى ملف CSV.</div>

        <div class="actions" style="margin-top:16px;">
            <a href="{{ route('sales-invoices.export', request()->only(['customer_id', 'payment_status', 'collection_status', 'issued_from', 'issued_to', 'due_from', 'due_to'])) }}"
               class="btn secondary"
               data-testid="sales-invoice-export-filtered-link">
                تصدير النتائج الحالية CSV
            </a>

            <a href="{{ route('sales-invoices.export') }}"
               class="btn secondary"
               data-testid="sales-invoice-export-all-link">
                تصدير كل الفواتير CSV
            </a>
        </div>
    </div>

    <div class="card" data-testid="sales-invoice-summary-card" style="margin-bottom:20px;">
        <h2>ملخص فواتير المبيعات</h2>
        <div class="muted">يعرض هذا الملخص نتائج الفواتير حسب الفلاتر الحالية.</div>

        <div class="detail-summary" style="margin-top:16px;">
            <div class="summary-item">
                <div class="summary-label">عدد الفواتير</div>
                <div class="summary-value" data-testid="sales-invoice-summary-count">{{ $salesInvoiceSummary['count'] }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي الفواتير</div>
                <div class="summary-value" data-testid="sales-invoice-summary-grand-total">{{ number_format($salesInvoiceSummary['grand_total'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي المدفوع</div>
                <div class="summary-value" data-testid="sales-invoice-summary-paid-amount">{{ number_format($salesInvoiceSummary['paid_amount'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي المتبقي</div>
                <div class="summary-value" data-testid="sales-invoice-summary-remaining-amount">{{ number_format($salesInvoiceSummary['remaining_amount'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">فواتير ذات متبقي</div>
                <div class="summary-value" data-testid="sales-invoice-summary-outstanding-count">{{ $salesInvoiceSummary['outstanding_count'] }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">فواتير مدفوعة</div>
                <div class="summary-value" data-testid="sales-invoice-summary-paid-count">{{ $salesInvoiceSummary['paid_count'] }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>رقم الفاتورة</th>
                        <th>العميل</th>
                        <th>الفرع</th>
                        <th>الحالة</th>
                        <th>حالة الدفع</th>
                        <th>الإجمالي</th>
                        <th>المتبقي</th>
                        <th>التاريخ</th>
                        <th>عرض</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td dir="ltr">{{ $invoice->invoice_number }}</td>
                            <td>{{ $invoice->customer?->name }}</td>
                            <td>{{ $invoice->branch?->name }}</td>
                            <td>
                                <span class="badge">{{ $invoice->displayStatus() }}</span>
                            </td>
                            <td>{{ $invoice->payment_status }}</td>
                            <td>{{ number_format((float) $invoice->grand_total, 2) }} ريال</td>
                            <td>{{ number_format((float) $invoice->remaining_amount, 2) }} ريال</td>
                            <td>{{ $invoice->issued_at?->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('sales-invoices.show', $invoice) }}"
                                   style="color:#8b5e3c;font-weight:700;">
                                    التفاصيل
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9">لا توجد فواتير بيع مسجلة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
