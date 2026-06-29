<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مركز المتابعات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 1180px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        .muted { color: #6b7280; font-size: 13px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 9px 13px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-weight: 700; }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        .btn.active { background: #92400e; color: #fff; }
        .btn.small { padding: 6px 10px; font-size: 12px; }
        input, select { border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; min-width: 220px; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .stat { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; }
        .stat-value { font-size: 24px; font-weight: 800; }
        .stat-label { color: #6b7280; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; }
        .badge.customer { background: #dbeafe; color: #1e40af; }
        .badge.supplier { background: #dcfce7; color: #166534; }
        .summary { max-width: 360px; white-space: pre-wrap; }
        .pagination { margin-top: 14px; }
        @media (max-width: 800px) {
            .stats { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            input, select { width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>مركز المتابعات</h1>
        <div class="muted">كل مواعيد المتابعة للعملاء والموردين من سجلات التواصل في مكان واحد.</div>

        <div class="stats">
            <div class="stat" data-testid="follow-ups-due-count">
                <div class="stat-value">{{ $dueCount }}</div>
                <div class="stat-label">مستحقة أو متأخرة</div>
            </div>

            <div class="stat" data-testid="follow-ups-upcoming-count">
                <div class="stat-value">{{ $upcomingCount }}</div>
                <div class="stat-label">قادمة</div>
            </div>

            <div class="stat" data-testid="follow-ups-all-count">
                <div class="stat-value">{{ $allCount }}</div>
                <div class="stat-label">كل المتابعات</div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('party-follow-ups.index', ['status' => 'due', 'q' => $search]) }}" class="btn {{ $status === 'due' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-due">المستحقة</a>
            <a href="{{ route('party-follow-ups.index', ['status' => 'upcoming', 'q' => $search]) }}" class="btn {{ $status === 'upcoming' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-upcoming">القادمة</a>
            <a href="{{ route('party-follow-ups.index', ['status' => 'all', 'q' => $search]) }}" class="btn {{ $status === 'all' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-all">الكل</a>
        </div>

        <form method="GET" action="{{ route('party-follow-ups.index') }}" class="actions" data-testid="follow-ups-search-form">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="بحث بالاسم أو الملخص" data-testid="follow-ups-search-input">
            <button type="submit" class="btn" data-testid="follow-ups-search-submit">بحث</button>
            <a href="{{ route('party-follow-ups.index') }}" class="btn secondary">إعادة ضبط</a>
        </form>
    </div>

    <div class="card">
        <h2>قائمة المتابعات</h2>

        <table data-testid="follow-ups-table">
            <thead>
                <tr>
                    <th>النوع</th>
                    <th>الاسم</th>
                    <th>نوع التواصل</th>
                    <th>تاريخ التواصل</th>
                    <th>تاريخ المتابعة</th>
                    <th>الملخص</th>
                    <th>الرابط</th>
                </tr>
            </thead>
            <tbody>
                @forelse($followUps as $followUp)
                    @php
                        $isCustomer = ! is_null($followUp->customer_id);
                        $party = $isCustomer ? $followUp->customer : $followUp->supplier;
                        $showRoute = $isCustomer
                            ? route('customers.show', $followUp->customer_id)
                            : route('suppliers.show', $followUp->supplier_id);

                        $typeLabels = [
                            'call' => 'اتصال',
                            'whatsapp' => 'واتساب',
                            'email' => 'إيميل',
                            'meeting' => 'اجتماع',
                            'other' => 'أخرى',
                        ];
                    @endphp

                    <tr data-testid="follow-up-row-{{ $followUp->id }}">
                        <td>
                            @if($isCustomer)
                                <span class="badge customer">عميل</span>
                            @else
                                <span class="badge supplier">مورد</span>
                            @endif
                        </td>
                        <td>{{ $party?->name ?: '-' }}</td>
                        <td>{{ $typeLabels[$followUp->contact_type] ?? $followUp->contact_type }}</td>
                        <td>{{ $followUp->contacted_at?->format('Y-m-d') ?: '-' }}</td>
                        <td><span class="badge">{{ $followUp->follow_up_at?->format('Y-m-d') ?: '-' }}</span></td>
                        <td class="summary">{{ $followUp->summary }}</td>
                        <td><a class="btn small secondary" href="{{ $showRoute }}" data-testid="follow-up-open-{{ $followUp->id }}">فتح السجل</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" data-testid="follow-ups-empty">لا توجد متابعات مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="pagination">
            {{ $followUps->links() }}
        </div>
    </div>
</div>
</body>
</html>
