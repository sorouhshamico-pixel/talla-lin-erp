<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل المورد</title>
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
<div class="container" data-testid="suppliers-show">
    <div class="card">
        <h1>تفاصيل المورد</h1>
        <div class="muted">عرض بيانات المورد الأساسية وحالة النشاط.</div>


        <div class="detail-summary" data-testid="suppliers-detail-summary">
            <div class="summary-item">
                <div class="summary-label">اسم المورد</div>
                <div class="summary-value">{{ $supplier->name }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">الهاتف</div>
                <div class="summary-value">{{ $supplier->phone ?: '-' }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">المدينة</div>
                <div class="summary-value">{{ $supplier->city ?: '-' }}</div>
            </div>

            <div class="summary-item">
                <div class="summary-label">الحالة</div>
                <div class="summary-value">
                    @if($supplier->is_active)
                        نشط
                    @else
                        غير نشط
                    @endif
                </div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('suppliers.index') }}" class="btn secondary" data-testid="suppliers-back-link">رجوع للموردين</a>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn" data-testid="suppliers-edit-link">تعديل المورد</a>
        
            <form method="POST" action="{{ route('suppliers.toggle-active', $supplier) }}" style="display:inline-block;" data-testid="suppliers-toggle-active-form">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn secondary" data-testid="suppliers-toggle-active-button">
                    {{ $supplier->is_active ? 'تعطيل المورد' : 'تفعيل المورد' }}
                </button>
            </form>
        
            <button type="button" class="btn secondary no-print" onclick="window.print()" data-testid="suppliers-print-button">طباعة بيانات المورد</button>
</div>
    </div>

    <div class="card">
        <div class="grid">
            <div class="field">
                <div class="label">اسم المورد</div>
                <div class="value">{{ $supplier->name }}</div>
            </div>

            <div class="field">
                <div class="label">الحالة</div>
                <div class="value">
                    @if($supplier->is_active)
                        <span class="badge active">نشط</span>
                    @else
                        <span class="badge inactive">غير نشط</span>
                    @endif
                </div>
            </div>

            <div class="field">
                <div class="label">الهاتف</div>
                <div class="value">{{ $supplier->phone ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">البريد الإلكتروني</div>
                <div class="value">{{ $supplier->email ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">المدينة</div>
                <div class="value">{{ $supplier->city ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">الرقم الضريبي</div>
                <div class="value">{{ $supplier->tax_number ?: ($supplier->vat_number ?: '-') }}</div>
            </div>

            <div class="field">
                <div class="label">السجل التجاري</div>
                <div class="value">{{ $supplier->commercial_registration ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">العنوان</div>
                <div class="value">{{ $supplier->address ?: '-' }}</div>
            </div>

            <div class="field">
                <div class="label">ملاحظات</div>
                <div class="value">{{ $supplier->notes ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

    <div class="card" data-testid="suppliers-notes-card">
        <h2>ملاحظات المورد</h2>
        <div class="muted">أضف ملاحظات داخلية مرتبطة بهذا السجل.</div>

        <form method="POST" action="{{ route('suppliers.notes.store', $supplier) }}" data-testid="suppliers-note-form" style="margin-top: 16px;">
            @csrf

            <label for="suppliers_note">الملاحظة</label>
            <textarea id="suppliers_note" name="note" required placeholder="اكتب الملاحظة هنا...">{{ old('note') }}</textarea>

            @error('note')
                <div class="muted">{{ $message }}</div>
            @enderror

            <div class="actions">
                <button type="submit" class="btn" data-testid="suppliers-note-submit">إضافة ملاحظة</button>
            </div>
        </form>

        @php
            $notes = \App\Models\PartyNote::query()
                ->where('supplier_id', $supplier->id)
                ->latest()
                ->limit(10)
                ->get();
        @endphp

        <div style="margin-top: 18px;" data-testid="suppliers-notes-list">
            @forelse($notes as $note)
                <div class="note-item" data-testid="suppliers-note-{{ $note->id }}">
                    <div class="note-meta">{{ $note->created_at?->format('Y-m-d H:i') }}</div>
                    <div class="note-text">{{ $note->note }}</div>

                    <form method="POST" action="{{ route('suppliers.notes.destroy', [$supplier, $note]) }}" style="margin-top: 10px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn small secondary" data-testid="suppliers-note-delete-{{ $note->id }}">حذف الملاحظة</button>
                    </form>
                </div>
            @empty
                <div class="muted" data-testid="suppliers-notes-empty">لا توجد ملاحظات بعد.</div>
            @endforelse
        </div>
    </div>


    <div class="card" data-testid="suppliers-attachments-card">
        <h2>مرفقات المورد</h2>
        <div class="muted">ارفع ملفات داخلية مرتبطة بهذا السجل.</div>

        <form method="POST" action="{{ route('suppliers.attachments.store', $supplier) }}" enctype="multipart/form-data" data-testid="suppliers-attachment-form" style="margin-top: 16px;">
            @csrf

            <label for="suppliers_attachment">المرفق</label>
            <input id="suppliers_attachment" type="file" name="attachment" required>

            @error('attachment')
                <div class="muted">{{ $message }}</div>
            @enderror

            <div class="actions">
                <button type="submit" class="btn" data-testid="suppliers-attachment-submit">رفع مرفق</button>
            </div>
        </form>

        @php
            $attachments = \App\Models\PartyAttachment::query()
                ->where('supplier_id', $supplier->id)
                ->latest()
                ->limit(10)
                ->get();
        @endphp

        <div style="margin-top: 18px;" data-testid="suppliers-attachments-list">
            @forelse($attachments as $attachment)
                <div class="attachment-item" data-testid="suppliers-attachment-{{ $attachment->id }}">
                    <strong>{{ $attachment->original_name }}</strong>
                    <div class="attachment-meta">
                        الحجم: {{ number_format(($attachment->size ?? 0) / 1024, 2) }} KB
                        — النوع: {{ $attachment->mime_type ?: '-' }}
                    </div>

                    <div class="actions">
                        <a class="btn small" href="{{ route('suppliers.attachments.download', [$supplier, $attachment]) }}" data-testid="suppliers-attachment-download-{{ $attachment->id }}">تحميل</a>

                        <form method="POST" action="{{ route('suppliers.attachments.destroy', [$supplier, $attachment]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn small secondary" data-testid="suppliers-attachment-delete-{{ $attachment->id }}">حذف</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="muted" data-testid="suppliers-attachments-empty">لا توجد مرفقات بعد.</div>
            @endforelse
        </div>
    </div>


    <div class="card" data-testid="suppliers-contact-logs-card">
        <h2>سجل تواصل المورد</h2>
        <div class="muted">سجّل آخر تواصل وما يلزم متابعته لاحقًا.</div>

        <form method="POST" action="{{ route('suppliers.contact-logs.store', $supplier) }}" data-testid="suppliers-contact-log-form" style="margin-top: 16px;">
            @csrf

            <div class="grid">
                <div class="field">
                    <label for="suppliers_contact_type">نوع التواصل</label>
                    <select id="suppliers_contact_type" name="contact_type" required>
                        <option value="call">اتصال</option>
                        <option value="whatsapp">واتساب</option>
                        <option value="email">إيميل</option>
                        <option value="meeting">اجتماع</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>

                <div class="field">
                    <label for="suppliers_contacted_at">تاريخ التواصل</label>
                    <input id="suppliers_contacted_at" type="date" name="contacted_at" value="{{ old('contacted_at', now()->toDateString()) }}">
                </div>

                <div class="field">
                    <label for="suppliers_follow_up_at">تاريخ المتابعة</label>
                    <input id="suppliers_follow_up_at" type="date" name="follow_up_at" value="{{ old('follow_up_at') }}">
                </div>
            </div>

            <label for="suppliers_contact_summary" style="margin-top: 14px;">ملخص التواصل</label>
            <textarea id="suppliers_contact_summary" name="summary" required placeholder="اكتب ملخص التواصل هنا...">{{ old('summary') }}</textarea>

            @error('summary')
                <div class="muted">{{ $message }}</div>
            @enderror

            <div class="actions">
                <button type="submit" class="btn" data-testid="suppliers-contact-log-submit">إضافة سجل تواصل</button>
            </div>
        </form>

        @php
            $contactLogs = \App\Models\PartyContactLog::query()
                ->where('supplier_id', $supplier->id)
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

        <div style="margin-top: 18px;" data-testid="suppliers-contact-logs-list">
            @forelse($contactLogs as $contactLog)
                <div class="contact-log-item" data-testid="suppliers-contact-log-{{ $contactLog->id }}">
                    <div class="contact-log-meta">
                        النوع: {{ $contactTypeLabels[$contactLog->contact_type] ?? $contactLog->contact_type }}
                        — تاريخ التواصل: {{ $contactLog->contacted_at?->format('Y-m-d') ?: '-' }}
                        — المتابعة: {{ $contactLog->follow_up_at?->format('Y-m-d') ?: '-' }}
                    </div>

                    <div class="contact-log-summary">{{ $contactLog->summary }}</div>

                    <form method="POST" action="{{ route('suppliers.contact-logs.destroy', [$supplier, $contactLog]) }}" style="margin-top: 10px;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn small secondary" data-testid="suppliers-contact-log-delete-{{ $contactLog->id }}">حذف سجل التواصل</button>
                    </form>
                </div>
            @empty
                <div class="muted" data-testid="suppliers-contact-logs-empty">لا توجد سجلات تواصل بعد.</div>
            @endforelse
        </div>
    </div>


    <div class="card" data-testid="suppliers-activity-timeline-entry-card">
        <h2>خط نشاط المورد</h2>
        <div class="muted">استعرض الملاحظات والمرفقات وسجلات التواصل في خط زمني واحد.</div>

        <div class="actions">
            <a href="{{ route('suppliers.activity-timeline.index', $supplier) }}" class="btn secondary" data-testid="suppliers-activity-timeline-link">عرض خط النشاط</a>
        </div>
    </div>


    @php
        $supplierFinancialSummary = app(\App\Services\PartyFinancialSummaryService::class)->supplierSummary($supplier->id);
    @endphp

    <div class="card" data-testid="suppliers-financial-summary-card">
        <h2>الملخص المالي للمورد</h2>
        <div class="muted">ملخص مالي محسوب من المصروفات المرتبطة بهذا المورد عند توفر البيانات.</div>

        <div class="grid" style="margin-top: 16px;">
            <div class="field" data-testid="suppliers-financial-total-card">
                <div class="muted">{{ $supplierFinancialSummary['total_label'] }}</div>
                <strong data-testid="suppliers-financial-total">{{ number_format($supplierFinancialSummary['total'], 2) }}</strong>
            </div>

            <div class="field" data-testid="suppliers-financial-paid-card">
                <div class="muted">{{ $supplierFinancialSummary['paid_label'] }}</div>
                <strong data-testid="suppliers-financial-paid">{{ number_format($supplierFinancialSummary['paid'], 2) }}</strong>
            </div>

            <div class="field" data-testid="suppliers-financial-pending-card">
                <div class="muted">{{ $supplierFinancialSummary['pending_label'] }}</div>
                <strong data-testid="suppliers-financial-pending">{{ number_format($supplierFinancialSummary['pending'], 2) }}</strong>
            </div>

            <div class="field" data-testid="suppliers-financial-count-card">
                <div class="muted">عدد الحركات</div>
                <strong data-testid="suppliers-financial-count">{{ $supplierFinancialSummary['count'] }}</strong>
            </div>
        </div>

        @unless($supplierFinancialSummary['has_data_source'])
            <div class="muted" style="margin-top: 12px;" data-testid="suppliers-financial-no-source">
                لم يتم العثور على مصدر مالي مباشر مرتبط بالموردين حتى الآن.
            </div>
        @endunless
    </div>

</body>
</html>
