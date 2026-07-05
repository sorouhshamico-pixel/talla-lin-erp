<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل العميل</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f6f7fb; color: #111827; margin: 0; padding: 24px; }
        .container { max-width: 980px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        h1 { margin: 0 0 8px; font-size: 28px; }
        .muted { color: #6b7280; font-size: 14px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; }
        .detail-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
        .summary-item { border: 1px solid #e5e7eb; border-radius: 12px; background: #fafafa; padding: 12px; }
        .summary-label { color: #6b7280; font-size: 12px; font-weight: 700; margin-bottom: 6px; }
        .summary-value { color: #111827; font-size: 15px; font-weight: 800; word-break: break-word; }
        .btn { border: 0; border-radius: 10px; padding: 11px 16px; background: #111827; color: #fff; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; white-space: nowrap; }
        .btn.secondary { background: #374151; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #fafafa; }
        .label { color: #6b7280; margin-bottom: 8px; font-size: 13px; font-weight: 700; }
        .value { font-size: 16px; font-weight: 700; word-break: break-word; }
        .badge { display: inline-block; border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 700; }
        .active { background: #dcfce7; color: #166534; }
        .inactive { background: #fee2e2; color: #991b1b; }
        @media (max-width: 800px) { .grid, .detail-summary { grid-template-columns: 1fr; } }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print, .actions { display: none !important; }
            .container { max-width: none; }
            .card { box-shadow: none; border-color: #d1d5db; }
        }
    
        textarea { width: 100%; min-height: 90px; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; box-sizing: border-box; resize: vertical; }
        .note-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; margin-bottom: 10px; background: #fafafa; }
        .note-meta { color: #6b7280; font-size: 12px; margin-bottom: 8px; }
        .note-text { white-space: pre-wrap; font-weight: 700; }


        input[type="file"] { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; box-sizing: border-box; background: #fff; }
        .attachment-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; margin-bottom: 10px; background: #fafafa; }
        .attachment-meta { color: #6b7280; font-size: 12px; margin-top: 6px; }


        select, input[type="date"] { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; box-sizing: border-box; background: #fff; }
        .contact-log-item { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; margin-bottom: 10px; background: #fafafa; }
        .contact-log-meta { color: #6b7280; font-size: 12px; margin-bottom: 8px; }
        .contact-log-summary { white-space: pre-wrap; font-weight: 700; }

</style>
</head>
<body>
<div class="container" data-testid="customers-show">
    <div class="card">
        <h1>تفاصيل العميل</h1>
        <div class="muted">عرض بيانات العميل الأساسية وحالة النشاط.</div>


        <div class="detail-summary" data-testid="customers-detail-summary">
            <div class="summary-item">
                <div class="summary-label">اسم العميل</div>
                <div class="summary-value">{{ $customer->name }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">الهاتف</div>
                <div class="summary-value">{{ $customer->phone ?: '-' }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">المدينة</div>
                <div class="summary-value">{{ $customer->city ?: '-' }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">الحالة</div>
                <div class="summary-value">
                    @if($customer->is_active)
                        نشط
                    @else
                        غير نشط
                    @endif
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('customers.index') }}" class="btn secondary" data-testid="customers-back-link">رجوع للعملاء</a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn" data-testid="customers-edit-link">تعديل العميل</a>
        
            <form method="POST" action="{{ route('customers.toggle-active', $customer) }}" style="display:inline-block;" data-testid="customers-toggle-active-form">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn secondary" data-testid="customers-toggle-active-button">
                    {{ $customer->is_active ? 'تعطيل العميل' : 'تفعيل العميل' }}
                </button>
            </form>
        
            <button type="button" class="btn secondary no-print" onclick="window.print()" data-testid="customers-print-button">طباعة بيانات العميل</button>
</div>
    </div>

    <div class="card">
        <div class="grid">
            <div class="field">
                <div class="label">اسم العميل</div>
                <div class="value">{{ $customer->name }}</div>
            </div>

            <div class="field">
                <div class="label">الحالة</div>
                <div class="value">
                    @if($customer->is_active)
                        <span class="badge active">نشط</span>
                    @else
                        <span class="badge inactive">غير نشط</span>
                    @endif
                </div>
            </div>

            <div class="field">
                <div class="label">الهاتف</div>
                <div class="value">{{ $customer->phone ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">البريد الإلكتروني</div>
                <div class="value">{{ $customer->email ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">المدينة</div>
                <div class="value">{{ $customer->city ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">الرقم الضريبي</div>
                <div class="value">{{ $customer->tax_number ?: ($customer->vat_number ?: '-') }}</div>
            </div>

            <div class="field">
                <div class="label">السجل التجاري</div>
                <div class="value">{{ $customer->commercial_registration ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">العنوان</div>
                <div class="value">{{ $customer->address ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">ملاحظات</div>
                <div class="value">{{ $customer->notes ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

    @php
        if (! isset($customerSalesInvoiceSummary)) {
            $customerSalesInvoiceQuery = $customer->salesInvoices();

            $customerSalesInvoiceSummary = [
                'count' => (clone $customerSalesInvoiceQuery)->count(),
                'grand_total' => round((float) (clone $customerSalesInvoiceQuery)->sum('grand_total'), 2),
                'paid_amount' => round((float) (clone $customerSalesInvoiceQuery)->sum('paid_amount'), 2),
                'remaining_amount' => round((float) (clone $customerSalesInvoiceQuery)->sum('remaining_amount'), 2),
            ];
        }
    @endphp

    <div class="card" data-testid="customer-sales-invoice-summary-card">
        <h2>ملخص فواتير مبيعات العميل</h2>
        <div class="muted">يعرض هذا الملخص فواتير المبيعات المرتبطة بهذا العميل.</div>

        <div class="detail-summary" style="margin-top: 16px;">
            <div class="summary-item">
                <div class="summary-label">عدد الفواتير</div>
                <div class="summary-value" data-testid="customer-sales-invoice-summary-count">{{ $customerSalesInvoiceSummary['count'] }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي الفواتير</div>
                <div class="summary-value" data-testid="customer-sales-invoice-summary-grand-total">{{ number_format($customerSalesInvoiceSummary['grand_total'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي المدفوع</div>
                <div class="summary-value" data-testid="customer-sales-invoice-summary-paid">{{ number_format($customerSalesInvoiceSummary['paid_amount'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي المتبقي</div>
                <div class="summary-value" data-testid="customer-sales-invoice-summary-remaining">{{ number_format($customerSalesInvoiceSummary['remaining_amount'], 2) }} ريال</div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('sales-invoices.index', ['customer_id' => $customer->id]) }}"
               class="btn secondary"
               data-testid="customer-sales-invoice-summary-link">
                عرض فواتير العميل
            </a>
        </div>
    </div>

    @php
        if (! isset($customerOutstandingSalesInvoiceSummary)) {
            $customerOutstandingSalesInvoiceQuery = $customer->salesInvoices()
                ->where('remaining_amount', '>', 0);

            $customerOutstandingSalesInvoiceSummary = [
                'count' => (clone $customerOutstandingSalesInvoiceQuery)->count(),
                'remaining_amount' => round((float) (clone $customerOutstandingSalesInvoiceQuery)->sum('remaining_amount'), 2),
            ];
        }
    @endphp

    <div class="card" data-testid="customer-outstanding-sales-invoice-summary-card">
        <h2>ملخص فواتير العميل ذات المبالغ المتبقية</h2>
        <div class="muted">يعرض هذا الملخص فواتير المبيعات التي لا يزال عليها مبلغ متبقٍ.</div>

        <div class="detail-summary" style="margin-top: 16px;">
            <div class="summary-item">
                <div class="summary-label">عدد الفواتير ذات المتبقي</div>
                <div class="summary-value" data-testid="customer-outstanding-sales-invoice-summary-count">{{ $customerOutstandingSalesInvoiceSummary['count'] }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي المتبقي</div>
                <div class="summary-value" data-testid="customer-outstanding-sales-invoice-summary-total">{{ number_format($customerOutstandingSalesInvoiceSummary['remaining_amount'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">حالة التحصيل</div>
                <div class="summary-value">
                    @if ($customerOutstandingSalesInvoiceSummary['count'] > 0)
                        يحتاج متابعة
                    @else
                        لا توجد مبالغ متبقية
                    @endif
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-label">عرض الفواتير</div>
                <div class="summary-value">
                    <a href="{{ route('sales-invoices.index', ['customer_id' => $customer->id, 'collection_status' => 'outstanding']) }}"
                       class="btn secondary"
                       data-testid="customer-outstanding-sales-invoice-summary-link">
                        عرض الفواتير ذات المتبقي
                    </a>
                </div>
            </div>
        </div>
    </div>

    @php
        if (! isset($customerPaidSalesInvoiceSummary)) {
            $customerPaidSalesInvoiceQuery = $customer->salesInvoices()
                ->where('payment_status', 'paid');

            $customerPaidSalesInvoiceSummary = [
                'count' => (clone $customerPaidSalesInvoiceQuery)->count(),
                'grand_total' => round((float) (clone $customerPaidSalesInvoiceQuery)->sum('grand_total'), 2),
            ];
        }
    @endphp

    <div class="card" data-testid="customer-paid-sales-invoice-summary-card">
        <h2>ملخص فواتير العميل المدفوعة بالكامل</h2>
        <div class="muted">يعرض هذا الملخص فواتير المبيعات التي تم سدادها بالكامل.</div>

        <div class="detail-summary" style="margin-top: 16px;">
            <div class="summary-item">
                <div class="summary-label">عدد الفواتير المدفوعة</div>
                <div class="summary-value" data-testid="customer-paid-sales-invoice-summary-count">{{ $customerPaidSalesInvoiceSummary['count'] }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">إجمالي الفواتير المدفوعة</div>
                <div class="summary-value" data-testid="customer-paid-sales-invoice-summary-total">{{ number_format($customerPaidSalesInvoiceSummary['grand_total'], 2) }} ريال</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">حالة التحصيل</div>
                <div class="summary-value">
                    @if ($customerPaidSalesInvoiceSummary['count'] > 0)
                        فواتير مدفوعة مسجلة
                    @else
                        لا توجد فواتير مدفوعة بالكامل
                    @endif
                </div>
            </div>

            <div class="summary-item">
                <div class="summary-label">عرض الفواتير</div>
                <div class="summary-value">
                    <a href="{{ route('sales-invoices.index', ['customer_id' => $customer->id, 'payment_status' => 'paid']) }}"
                       class="btn secondary"
                       data-testid="customer-paid-sales-invoice-summary-link">
                        عرض الفواتير المدفوعة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="customer-sales-invoice-export-links-card">
        <h2>تصدير فواتير مبيعات العميل</h2>
        <div class="muted">روابط سريعة لتصدير فواتير هذا العميل حسب حالة التحصيل.</div>

        <div class="actions">
            <a href="{{ route('sales-invoices.export', ['customer_id' => $customer->id]) }}"
               class="btn secondary"
               data-testid="customer-sales-invoice-export-all-link">
                تصدير كل فواتير العميل CSV
            </a>

            <a href="{{ route('sales-invoices.export', ['customer_id' => $customer->id, 'collection_status' => 'outstanding']) }}"
               class="btn secondary"
               data-testid="customer-sales-invoice-export-outstanding-link">
                تصدير الفواتير ذات المتبقي CSV
            </a>

            <a href="{{ route('sales-invoices.export', ['customer_id' => $customer->id, 'payment_status' => 'paid']) }}"
               class="btn secondary"
               data-testid="customer-sales-invoice-export-paid-link">
                تصدير الفواتير المدفوعة CSV
            </a>
        </div>
    </div>

    @php
        if (! isset($customerRecentSalesInvoices)) {
            $customerRecentSalesInvoices = $customer->salesInvoices()
                ->latest('issued_at')
                ->latest('id')
                ->limit(5)
                ->get();
        }
    @endphp

    <div class="card" data-testid="customer-recent-sales-invoices-card">
        <h2>آخر فواتير مبيعات العميل</h2>
        <div class="muted">يعرض هذا القسم آخر 5 فواتير مبيعات مرتبطة بهذا العميل.</div>

        <div style="margin-top: 16px;" data-testid="customer-recent-sales-invoices-list">
            @forelse ($customerRecentSalesInvoices as $invoice)
                <div class="field" data-testid="customer-recent-sales-invoice-row" style="margin-bottom: 10px;">
                    <div class="label">
                        {{ $invoice->issued_at?->format('Y-m-d') ?: '-' }}
                        —
                        {{ $invoice->displayPaymentStatus() }}
                    </div>

                    <div class="value">
                        {{ $invoice->invoice_number }}
                    </div>

                    <div class="muted" style="margin-top: 6px;">
                        الإجمالي: {{ number_format((float) $invoice->grand_total, 2) }} ريال
                        —
                        المدفوع: {{ number_format((float) $invoice->paid_amount, 2) }} ريال
                        —
                        المتبقي: {{ number_format((float) $invoice->remaining_amount, 2) }} ريال
                    </div>

                    <div class="actions">
                        <a href="{{ route('sales-invoices.show', $invoice) }}"
                           class="btn secondary"
                           data-testid="customer-recent-sales-invoice-show-link">
                            فتح الفاتورة
                        </a>
                    </div>
                </div>
            @empty
                <div class="muted" data-testid="customer-recent-sales-invoices-empty">لا توجد فواتير مبيعات مرتبطة بهذا العميل بعد.</div>
            @endforelse
        </div>
    </div>

    <div class="card" data-testid="customers-notes-card">
        <h2>ملاحظات العميل</h2>
        <div class="muted">أضف ملاحظات داخلية مرتبطة بهذا السجل.</div>

        <form method="POST" action="{{ route('customers.notes.store', $customer) }}" data-testid="customers-note-form" style="margin-top: 16px;">
            @csrf

            <label for="customers_note">الملاحظة</label>
            <textarea id="customers_note" name="note" required placeholder="اكتب الملاحظة هنا...">{{ old('note') }}</textarea>

            @error('note')
                <div class="muted">{{ $message }}</div>
            @enderror

            <div class="actions">
                <button type="submit" class="btn" data-testid="customers-note-submit">إضافة ملاحظة</button>
            </div>
        </form>

        @php
            $notes = \App\Models\PartyNote::query()
                ->where('customer_id', $customer->id)
                ->latest()
                ->limit(10)
                ->get();
        @endphp

        <div style="margin-top: 18px;" data-testid="customers-notes-list">
            @forelse($notes as $note)
                <div class="note-item" data-testid="customers-note-{{ $note->id }}">
                    <div class="note-meta">{{ $note->created_at?->format('Y-m-d H:i') }}</div>
                    <div class="note-text">{{ $note->note }}</div>

                    <form method="POST" action="{{ route('customers.notes.destroy', [$customer, $note]) }}" style="margin-top: 10px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn small secondary" data-testid="customers-note-delete-{{ $note->id }}">حذف الملاحظة</button>
                    </form>
                </div>
            @empty
                <div class="muted" data-testid="customers-notes-empty">لا توجد ملاحظات بعد.</div>
            @endforelse
        </div>
    </div>


    <div class="card" data-testid="customers-attachments-card">
        <h2>مرفقات العميل</h2>
        <div class="muted">ارفع ملفات داخلية مرتبطة بهذا السجل.</div>

        <form method="POST" action="{{ route('customers.attachments.store', $customer) }}" enctype="multipart/form-data" data-testid="customers-attachment-form" style="margin-top: 16px;">
            @csrf

            <label for="customers_attachment">المرفق</label>
            <input id="customers_attachment" type="file" name="attachment" required>

            @error('attachment')
                <div class="muted">{{ $message }}</div>
            @enderror

            <div class="actions">
                <button type="submit" class="btn" data-testid="customers-attachment-submit">رفع مرفق</button>
            </div>
        </form>

        @php
            $attachments = \App\Models\PartyAttachment::query()
                ->where('customer_id', $customer->id)
                ->latest()
                ->limit(10)
                ->get();
        @endphp

        <div style="margin-top: 18px;" data-testid="customers-attachments-list">
            @forelse($attachments as $attachment)
                <div class="attachment-item" data-testid="customers-attachment-{{ $attachment->id }}">
                    <strong>{{ $attachment->original_name }}</strong>
                    <div class="attachment-meta">
                        الحجم: {{ number_format(($attachment->size ?? 0) / 1024, 2) }} KB
                        — النوع: {{ $attachment->mime_type ?: '-' }}
                    </div>

                    <div class="actions">
                        <a class="btn small" href="{{ route('customers.attachments.download', [$customer, $attachment]) }}" data-testid="customers-attachment-download-{{ $attachment->id }}">تحميل</a>

                        <form method="POST" action="{{ route('customers.attachments.destroy', [$customer, $attachment]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn small secondary" data-testid="customers-attachment-delete-{{ $attachment->id }}">حذف</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="muted" data-testid="customers-attachments-empty">لا توجد مرفقات بعد.</div>
            @endforelse
        </div>
    </div>


    <div class="card" data-testid="customers-contact-logs-card">
        <h2>سجل تواصل العميل</h2>
        <div class="muted">سجّل آخر تواصل وما يلزم متابعته لاحقًا.</div>

        <form method="POST" action="{{ route('customers.contact-logs.store', $customer) }}" data-testid="customers-contact-log-form" style="margin-top: 16px;">
            @csrf

            <div class="grid">
                <div class="field">
                    <label for="customers_contact_type">نوع التواصل</label>
                    <select id="customers_contact_type" name="contact_type" required>
                        <option value="call">اتصال</option>
                        <option value="whatsapp">واتساب</option>
                        <option value="email">إيميل</option>
                        <option value="meeting">اجتماع</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>

                <div class="field">
                    <label for="customers_contacted_at">تاريخ التواصل</label>
                    <input id="customers_contacted_at" type="date" name="contacted_at" value="{{ old('contacted_at', now()->toDateString()) }}">
                </div>

                <div class="field">
                    <label for="customers_follow_up_at">تاريخ المتابعة</label>
                    <input id="customers_follow_up_at" type="date" name="follow_up_at" value="{{ old('follow_up_at') }}">
                </div>
            </div>

            <label for="customers_contact_summary" style="margin-top: 14px;">ملخص التواصل</label>
            <textarea id="customers_contact_summary" name="summary" required placeholder="اكتب ملخص التواصل هنا...">{{ old('summary') }}</textarea>

            @error('summary')
                <div class="muted">{{ $message }}</div>
            @enderror

            <div class="actions">
                <button type="submit" class="btn" data-testid="customers-contact-log-submit">إضافة سجل تواصل</button>
            </div>
        </form>

        @php
            $contactLogs = \App\Models\PartyContactLog::query()
                ->where('customer_id', $customer->id)
                ->latest('contacted_at')
                ->latest()
                ->limit(10)
                ->get();

            $contactTypeLabels = [
                'call' => 'اتصال',
                'whatsapp' => 'واتساب',
                'email' => 'إيميل',
                'meeting' => 'اجتماع',
                'other' => 'أخرى',
            ];
        @endphp

        <div style="margin-top: 18px;" data-testid="customers-contact-logs-list">
            @forelse($contactLogs as $contactLog)
                <div class="contact-log-item" data-testid="customers-contact-log-{{ $contactLog->id }}">
                    <div class="contact-log-meta">
                        النوع: {{ $contactTypeLabels[$contactLog->contact_type] ?? $contactLog->contact_type }}
                        — تاريخ التواصل: {{ $contactLog->contacted_at?->format('Y-m-d') ?: '-' }}
                        — المتابعة: {{ $contactLog->follow_up_at?->format('Y-m-d') ?: '-' }}
                    </div>

                    <div class="contact-log-summary">{{ $contactLog->summary }}</div>

                    <form method="POST" action="{{ route('customers.contact-logs.destroy', [$customer, $contactLog]) }}" style="margin-top: 10px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn small secondary" data-testid="customers-contact-log-delete-{{ $contactLog->id }}">حذف سجل التواصل</button>
                    </form>
                </div>
            @empty
                <div class="muted" data-testid="customers-contact-logs-empty">لا توجد سجلات تواصل بعد.</div>
            @endforelse
        </div>
    </div>


    <div class="card" data-testid="customers-activity-timeline-entry-card">
        <h2>خط نشاط العميل</h2>
        <div class="muted">استعرض الملاحظات والمرفقات وسجلات التواصل في خط زمني واحد.</div>

        <div class="actions">
            <a href="{{ route('customers.activity-timeline.index', $customer) }}" class="btn secondary" data-testid="customers-activity-timeline-link">عرض خط النشاط</a>
        </div>
    </div>


    @php
        $customerFinancialSummary = app(\App\Services\PartyFinancialSummaryService::class)->customerSummary($customer->id);
    @endphp

    <div class="card" data-testid="customers-financial-summary-card">
        <h2>الملخص المالي للعميل</h2>
        <div class="muted">ملخص مالي محسوب من الإيرادات المرتبطة بهذا العميل عند توفر البيانات.</div>

        <div class="grid" style="margin-top: 16px;">
            <div class="field" data-testid="customers-financial-total-card">
                <div class="muted">{{ $customerFinancialSummary['total_label'] }}</div>
                <strong data-testid="customers-financial-total">{{ number_format($customerFinancialSummary['total'], 2) }}</strong>
            </div>

            <div class="field" data-testid="customers-financial-paid-card">
                <div class="muted">{{ $customerFinancialSummary['paid_label'] }}</div>
                <strong data-testid="customers-financial-paid">{{ number_format($customerFinancialSummary['paid'], 2) }}</strong>
            </div>

            <div class="field" data-testid="customers-financial-pending-card">
                <div class="muted">{{ $customerFinancialSummary['pending_label'] }}</div>
                <strong data-testid="customers-financial-pending">{{ number_format($customerFinancialSummary['pending'], 2) }}</strong>
            </div>

            <div class="field" data-testid="customers-financial-count-card">
                <div class="muted">عدد الحركات</div>
                <strong data-testid="customers-financial-count">{{ $customerFinancialSummary['count'] }}</strong>
            </div>
        </div>

        @unless($customerFinancialSummary['has_data_source'])
            <div class="muted" style="margin-top: 12px;" data-testid="customers-financial-no-source">
                لم يتم العثور على مصدر مالي مباشر مرتبط بالعملاء حتى الآن.
            </div>
        @endunless
    </div>


    <div class="card" data-testid="customers-statement-entry-card">
        <h2>كشف حساب العميل</h2>
        <div class="muted">استعرض كشف الحساب والحركات المالية المرتبطة بهذا السجل.</div>

        <div class="actions">
            <a href="{{ route('customers.statement', $customer) }}" class="btn secondary" data-testid="customers-statement-link">عرض كشف الحساب</a>
        </div>
    </div>


    @php
        $customerPartyTags = \App\Models\PartyTag::query()
            ->active()
            ->forCustomers()
            ->orderBy('name')
            ->get();
    @endphp

    <div class="card" data-testid="customers-classification-card">
        <h2>تصنيف العميل</h2>
        <div class="muted">التصنيف الحالي:
            <strong data-testid="customers-current-classification">
                {{ $customer->partyTag?->name ?: 'بدون تصنيف' }}
            </strong>
        </div>

        <form method="POST" action="{{ route('customers.classification.update', $customer) }}" class="actions" data-testid="customers-classification-form">
            @csrf

            <select name="party_tag_id" data-testid="customers-classification-select">
                <option value="">بدون تصنيف</option>
                @foreach($customerPartyTags as $partyTag)
                    <option value="{{ $partyTag->id }}" @selected($customer->party_tag_id === $partyTag->id)>
                        {{ $partyTag->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn" data-testid="customers-classification-submit">تحديث التصنيف</button>
            <a href="{{ route('party-tags.index') }}" class="btn secondary" data-testid="customers-party-tags-link">إدارة التصنيفات</a>
        </form>
    </div>


    @php
        $customerDuplicateGroups = app(\App\Services\PartyDuplicateService::class)->customerDuplicates($customer);
        $customerDuplicateCount = collect($customerDuplicateGroups)->sum(fn ($items) => $items->count());
    @endphp

    <div class="card" data-testid="customers-duplicate-warning-card">
        <h2>فحص تكرار العميل</h2>

        @if($customerDuplicateCount > 0)
            <div class="muted" data-testid="customers-duplicate-warning-message">
                يوجد { $customerDuplicateCount } سجل محتمل التكرار مع هذا السجل حسب الهاتف أو البريد الإلكتروني.
            </div>

            @foreach($customerDuplicateGroups as $duplicateField => $duplicateItems)
                @foreach($duplicateItems as $duplicateItem)
                    <div class="field" style="margin-top: 10px;" data-testid="customers-duplicate-item-{{ $duplicateField }}-{{ $duplicateItem['record']->id }}">
                        <div class="muted">{{ $duplicateItem['field_label'] }}: {{ $duplicateItem['display_value'] }}</div>
                        <strong>{{ $duplicateItem['record']->name }}</strong>
                    </div>
                @endforeach
            @endforeach
        @else
            <div class="muted" data-testid="customers-duplicate-clean-message">لا توجد تكرارات واضحة لهذا السجل.</div>
        @endif

        <div class="actions">
            <a href="{{ route('party-duplicates.index') }}" class="btn secondary" data-testid="customers-duplicates-center-link">فتح مركز التكرارات</a>
        </div>
    </div>

</body>
</html>
