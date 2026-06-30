<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تفاصيل التصنيف - {{ $partyTag->name }}</title>
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
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .stat { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; }
        .stat-value { font-size: 24px; font-weight: 800; }
        .stat-label { color: #6b7280; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; }
        @media (max-width: 800px) {
            .stats { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>تفاصيل التصنيف: {{ $partyTag->name }}</h1>
        <div class="muted">{{ $partyTag->description ?: 'لا يوجد وصف.' }}</div>

        <div class="actions">
            <a href="{{ route('party-tags.index') }}" class="btn secondary" data-testid="party-tag-back-link">العودة للتصنيفات</a>
        </div>

        <div class="stats">
            <div class="stat" data-testid="party-tag-customers-count">
                <div class="stat-value">{{ $partyTag->customers()->count() }}</div>
                <div class="stat-label">عملاء مرتبطون</div>
            </div>

            <div class="stat" data-testid="party-tag-suppliers-count">
                <div class="stat-value">{{ $partyTag->suppliers()->count() }}</div>
                <div class="stat-label">موردون مرتبطون</div>
            </div>

            <div class="stat" data-testid="party-tag-status">
                <div class="stat-value">{{ $partyTag->is_active ? 'نشط' : 'غير نشط' }}</div>
                <div class="stat-label">حالة التصنيف</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="party-tag-customers-card">
        <h2>العملاء المرتبطون</h2>

        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>البريد</th>
                    <th>الرابط</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr data-testid="party-tag-customer-row-{{ $customer->id }}">
                        <td>{{ $customer->name }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td><a href="{{ route('customers.show', $customer) }}" class="btn secondary">فتح العميل</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" data-testid="party-tag-customers-empty">لا يوجد عملاء مرتبطون بهذا التصنيف.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $customers->links() }}
    </div>

    <div class="card" data-testid="party-tag-suppliers-card">
        <h2>الموردون المرتبطون</h2>

        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>البريد</th>
                    <th>الرابط</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr data-testid="party-tag-supplier-row-{{ $supplier->id }}">
                        <td>{{ $supplier->name }}</td>
                        <td>{{ $supplier->phone ?? '-' }}</td>
                        <td>{{ $supplier->email ?? '-' }}</td>
                        <td><a href="{{ route('suppliers.show', $supplier) }}" class="btn secondary">فتح المورد</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="muted" data-testid="party-tag-suppliers-empty">لا يوجد موردون مرتبطون بهذا التصنيف.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $suppliers->links() }}
    </div>
</div>
</body>
</html>
