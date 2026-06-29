<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>العملاء</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f6f7fb; color: #111827; margin: 0; padding: 24px; }
        .container { max-width: 1180px; margin: 0 auto; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 20px; margin-bottom: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, .05); }
        h1 { margin: 0 0 8px; font-size: 28px; }
        .muted { color: #6b7280; font-size: 14px; }
        .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
        .metric { border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; background: #fafafa; }
        .metric-label { color: #6b7280; margin-bottom: 8px; font-size: 13px; }
        .metric-value { font-size: 24px; font-weight: 800; }
        .filters { display: grid; grid-template-columns: 2fr 1fr auto auto; gap: 12px; align-items: end; }
        label { display: block; margin-bottom: 6px; font-weight: 700; font-size: 14px; }
        input, select { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; box-sizing: border-box; }
        .btn { border: 0; border-radius: 10px; padding: 11px 16px; background: #111827; color: #fff; cursor: pointer; text-decoration: none; display: inline-block; text-align: center; white-space: nowrap; }
        .btn.secondary { background: #374151; }
        .btn.small { padding: 7px 10px; font-size: 13px; }
        .status { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px; margin-bottom: 18px; }
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 980px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 12px; text-align: right; vertical-align: top; }
        th { background: #f9fafb; color: #374151; font-size: 14px; }
        .badge { display: inline-block; border-radius: 999px; padding: 5px 10px; font-size: 12px; font-weight: 700; }
        .active { background: #dcfce7; color: #166534; }
        .inactive { background: #fee2e2; color: #991b1b; }
        @media (max-width: 900px) { .summary, .filters { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container" data-testid="customers-index">
    <div class="card">
        <h1>العملاء</h1>
        <div class="muted">إدارة بيانات العملاء الأساسية وحالة النشاط.</div>
    </div>

    @if (session('status'))
        <div class="status" data-testid="customers-status-message">{{ session('status') }}</div>
    @endif

    <div class="summary">
        <div class="metric" data-testid="customers-summary-total">
            <div class="metric-label">إجمالي العملاء</div>
            <div class="metric-value">{{ number_format((int) $summary['total']) }}</div>
        </div>
        <div class="metric" data-testid="customers-summary-active">
            <div class="metric-label">عملاء نشطون</div>
            <div class="metric-value">{{ number_format((int) $summary['active']) }}</div>
        </div>
        <div class="metric" data-testid="customers-summary-inactive">
            <div class="metric-label">عملاء غير نشطين</div>
            <div class="metric-value">{{ number_format((int) $summary['inactive']) }}</div>
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('customers.index') }}" class="filters" data-testid="customers-filter-form">
            <div>
                <label for="q">بحث</label>
                <input id="q" type="text" name="q" value="{{ $filters['q'] }}" placeholder="ابحث بالاسم، الهاتف، البريد، المدينة، الرقم الضريبي">
            </div>

            <div>
                <label for="is_active">الحالة</label>
                <select id="is_active" name="is_active">
                    <option value="">كل الحالات</option>
                    <option value="1" @selected($filters['is_active'] === '1')>نشط</option>
                    <option value="0" @selected($filters['is_active'] === '0')>غير نشط</option>
                </select>
            </div>

            <div><button type="submit" class="btn">تطبيق الفلاتر</button></div>

            <div>
                <a href="{{ route('customers.export-template') }}" class="btn secondary" data-testid="customers-export-template-link">تحميل قالب العملاء CSV</a>
                <a href="{{ route('customers.export', request()->only(['q', 'is_active'])) }}" class="btn secondary" data-testid="customers-export-link">تصدير العملاء CSV</a>
                <a href="{{ route('customers.create') }}" class="btn secondary" data-testid="customers-create-link">إضافة عميل</a>
            </div>
        
            <div>
                <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data" data-testid="customers-import-form">
                    @csrf
                    <label for="customers_csv_file">استيراد عملاء من CSV</label>
                    <input id="customers_csv_file" type="file" name="csv_file" accept=".csv,text/csv" required>
                    <button type="submit" class="btn secondary" data-testid="customers-import-submit">استيراد CSV</button>
                    <div class="muted">استخدم قالب العملاء CSV ثم ارفع الملف هنا.</div>
                </form>
            </div>
</form>
    </div>

    <div class="card">
        <form method="POST" action="{{ route('customers.bulk-status') }}" data-testid="customers-bulk-status-form">
            @csrf
            @method('PATCH')

            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:14px;">
                <button type="submit" name="is_active" value="1" class="btn small" data-testid="customers-bulk-activate">تفعيل العملاء المحددين</button>
                <button type="submit" name="is_active" value="0" class="btn small secondary" data-testid="customers-bulk-deactivate">تعطيل العملاء المحددين</button>
            </div>

        <div class="table-wrapper">
            <table data-testid="customers-table">
                <thead>
                <tr>
                    <th>تحديد</th>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>البريد الإلكتروني</th>
                    <th>المدينة</th>
                    <th>الرقم الضريبي</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td>
                            <input
                                type="checkbox"
                                name="ids[]"
                                value="{{ $customer->id }}"
                                aria-label="تحديد عميل"
                                data-testid="customers-bulk-checkbox-{{ $customer->id }}"
                            >
                        </td>
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->phone ?: '-' }}</td>
                        <td>{{ $customer->email ?: '-' }}</td>
                        <td>{{ $customer->city ?: '-' }}</td>
                        <td>{{ $customer->tax_number ?: '-' }}</td>
                        <td>
                            @if ($customer->is_active)
                                <span class="badge active">نشط</span>
                            @else
                                <span class="badge inactive">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <a
                                href="{{ route('customers.edit', $customer->id) }}"
                                class="btn small secondary"
                                data-testid="customers-edit-link-{{ $customer->id }}"
                            >
                                تعديل
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted">لا توجد بيانات عملاء مطابقة.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        </form>
    </div>
</div>
</body>
</html>
