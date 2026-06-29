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

</body>
</html>
