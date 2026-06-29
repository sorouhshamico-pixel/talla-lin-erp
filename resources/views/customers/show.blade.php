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
        .btn { border: 0; border-radius: 10px; padding: 11px 16px; background: #111827; color: #fff; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; white-space: nowrap; }
        .btn.secondary { background: #374151; }
        .grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .field { border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #fafafa; }
        .label { color: #6b7280; margin-bottom: 8px; font-size: 13px; font-weight: 700; }
        .value { font-size: 16px; font-weight: 700; word-break: break-word; }
        .badge { display: inline-block; border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 700; }
        .active { background: #dcfce7; color: #166534; }
        .inactive { background: #fee2e2; color: #991b1b; }
        @media (max-width: 800px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="customers-show">
    <div class="card">
        <h1>تفاصيل العميل</h1>
        <div class="muted">عرض بيانات العميل الأساسية وحالة النشاط.</div>

        <div class="actions">
            <a href="{{ route('customers.index') }}" class="btn secondary" data-testid="customers-back-link">رجوع للعملاء</a>
            <a href="{{ route('customers.edit', $customer) }}" class="btn" data-testid="customers-edit-link">تعديل العميل</a>
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
</body>
</html>
