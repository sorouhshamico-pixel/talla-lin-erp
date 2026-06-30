<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مركز كشف التكرارات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 1180px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        h2 { margin: 0 0 10px; font-size: 20px; }
        h3 { margin: 18px 0 10px; font-size: 17px; }
        .muted { color: #6b7280; font-size: 13px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 9px 13px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-weight: 700; }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        .stats { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .stat { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; }
        .stat-value { font-size: 24px; font-weight: 800; }
        .stat-label { color: #6b7280; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; }
        .badge.customer { background: #dbeafe; color: #1e40af; }
        .badge.supplier { background: #dcfce7; color: #166534; }
        .duplicate-box { border: 1px solid #f59e0b; background: #fffbeb; border-radius: 14px; padding: 14px; margin-top: 14px; }
        @media (max-width: 800px) {
            .stats { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            body { padding: 14px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>مركز كشف التكرارات</h1>
        <div class="muted">يعرض السجلات المحتمل تكرارها حسب الهاتف أو البريد الإلكتروني للعملاء والموردين.</div>

        <div class="actions">
            <a href="{{ route('customers.index') }}" class="btn secondary" data-testid="duplicates-customers-link">العملاء</a>
            <a href="{{ route('suppliers.index') }}" class="btn secondary" data-testid="duplicates-suppliers-link">الموردون</a>
        </div>

        <div class="stats">
            <div class="stat" data-testid="duplicates-total-groups">
                <div class="stat-value">{{ $totalGroups }}</div>
                <div class="stat-label">مجموع مجموعات التكرار</div>
            </div>

            <div class="stat" data-testid="duplicates-total-records">
                <div class="stat-value">{{ $totalRecords }}</div>
                <div class="stat-label">عدد السجلات داخل مجموعات التكرار</div>
            </div>
        </div>
    </div>

    @php
        $sections = [
            'customer_phone' => ['title' => 'تكرار هواتف العملاء', 'party_label' => 'عميل', 'badge' => 'customer', 'route' => 'customers.show'],
            'customer_email' => ['title' => 'تكرار بريد العملاء', 'party_label' => 'عميل', 'badge' => 'customer', 'route' => 'customers.show'],
            'supplier_phone' => ['title' => 'تكرار هواتف الموردين', 'party_label' => 'مورد', 'badge' => 'supplier', 'route' => 'suppliers.show'],
            'supplier_email' => ['title' => 'تكرار بريد الموردين', 'party_label' => 'مورد', 'badge' => 'supplier', 'route' => 'suppliers.show'],
        ];
    @endphp

    @foreach($sections as $sectionKey => $section)
        <div class="card" data-testid="duplicate-section-{{ $sectionKey }}">
            <h2>{{ $section['title'] }}</h2>

            @forelse($groups[$sectionKey] as $groupIndex => $group)
                <div class="duplicate-box" data-testid="duplicate-group-{{ $sectionKey }}-{{ $groupIndex }}">
                    <h3>
                        <span class="badge {{ $section['badge'] }}">{{ $section['party_label'] }}</span>
                        {{ $group['field_label'] }}:
                        <strong>{{ $group['display_value'] }}</strong>
                    </h3>

                    <div class="muted">
                        عدد السجلات المتشابهة: <strong>{{ $group['count'] }}</strong>
                    </div>

                    <table>
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th>القيمة</th>
                                <th>الرابط</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group['records'] as $record)
                                <tr data-testid="duplicate-record-{{ $sectionKey }}-{{ $record->id }}">
                                    <td>{{ $record->name }}</td>
                                    <td>{{ $record->{$group['field']} }}</td>
                                    <td>
                                        <a href="{{ route($section['route'], $record->id) }}" class="btn secondary">
                                            فتح السجل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <div class="muted" data-testid="duplicate-empty-{{ $sectionKey }}">لا توجد تكرارات في هذا القسم.</div>
            @endforelse
        </div>
    @endforeach
</div>
</body>
</html>
