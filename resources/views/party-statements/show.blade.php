<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ $party->name }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 1180px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        h2 { margin: 0 0 10px; font-size: 20px; }
        .muted { color: #6b7280; font-size: 13px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 9px 13px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-weight: 700; }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        input { border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .stat { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; }
        .stat-value { font-size: 22px; font-weight: 800; }
        .stat-label { color: #6b7280; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .description { max-width: 360px; white-space: pre-wrap; }
        .positive { color: #166534; font-weight: 800; }
        .negative { color: #991b1b; font-weight: 800; }
        @media (max-width: 800px) {
            .stats { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            input { width: 100%; box-sizing: border-box; }
            body { padding: 14px; }
        }
        @media print {
            body { background: #fff; padding: 0; }
            .card { box-shadow: none; border: 1px solid #e5e7eb; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>{{ $title }}</h1>
        <div class="muted">
            {{ $partyTypeLabel }}: <strong>{{ $party->name }}</strong>
        </div>

        <div class="actions no-print">
            <a href="{{ $backRoute }}" class="btn secondary" data-testid="statement-back-link">العودة للتفاصيل</a>
            <a href="{{ $exportRoute }}" class="btn" data-testid="statement-export-link">تصدير CSV</a>
            <button type="button" class="btn secondary" onclick="window.print()" data-testid="statement-print-button">طباعة</button>
        </div>

        @if(($partyType ?? null) === 'customer')
            <div class="card" data-testid="customer-statement-sales-invoice-source" style="margin-top: 16px;">
                <strong>مصدر كشف الحساب:</strong>
                فواتير المبيعات ودفعات فواتير المبيعات.
                <div class="muted" style="margin-top: 8px;">
                    المدين يمثل قيمة فواتير البيع على العميل، والدائن يمثل الدفعات المسجلة من العميل.
                </div>
            </div>
        @endif

        <form method="GET" class="actions no-print" data-testid="statement-filter-form">
            <label>
                من
                <input type="date" name="from" value="{{ $from }}" data-testid="statement-from-input">
            </label>

            <label>
                إلى
                <input type="date" name="to" value="{{ $to }}" data-testid="statement-to-input">
            </label>

            <button type="submit" class="btn" data-testid="statement-filter-submit">تطبيق</button>
            <a href="{{ url()->current() }}" class="btn secondary">إعادة ضبط</a>
        </form>

        <div class="stats">
            <div class="stat" data-testid="statement-count-card">
                <div class="stat-value">{{ $statement['count'] }}</div>
                <div class="stat-label">عدد الحركات</div>
            </div>

            <div class="stat" data-testid="statement-debit-card">
                <div class="stat-value">{{ number_format($statement['total_debit'], 2) }}</div>
                <div class="stat-label">مدين</div>
            </div>

            <div class="stat" data-testid="statement-credit-card">
                <div class="stat-value">{{ number_format($statement['total_credit'], 2) }}</div>
                <div class="stat-label">دائن</div>
            </div>

            <div class="stat" data-testid="statement-balance-card">
                <div class="stat-value {{ $statement['balance'] >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($statement['balance'], 2) }}
                </div>
                <div class="stat-label">الرصيد</div>
            </div>
        </div>

        @unless($statement['has_data_source'])
            <div class="muted" style="margin-top: 12px;" data-testid="statement-no-source">
                لا يوجد مصدر مالي مباشر متاح لهذا الكشف حتى الآن.
            </div>
        @endunless
    </div>

    <div class="card" data-testid="statement-table-card">
        <h2>الحركات</h2>

        <table data-testid="statement-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>النوع</th>
                    <th>الوصف</th>
                    <th>الحالة</th>
                    <th>مدين</th>
                    <th>دائن</th>
                    <th>الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statement['rows'] as $index => $row)
                    <tr data-testid="statement-row-{{ $index }}">
                        <td>{{ $row['date'] }}</td>
                        <td>{{ $row['type'] }}</td>
                        <td class="description">{{ $row['description'] }}</td>
                        <td>{{ $row['status'] }}</td>
                        <td>{{ number_format($row['debit'], 2) }}</td>
                        <td>{{ number_format($row['credit'], 2) }}</td>
                        <td class="{{ $row['balance'] >= 0 ? 'positive' : 'negative' }}">{{ number_format($row['balance'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" data-testid="statement-empty">لا توجد حركات في كشف الحساب للفترة المحددة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
