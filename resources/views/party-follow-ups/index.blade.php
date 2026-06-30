<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مركز المتابعات</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 1240px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        .muted { color: #6b7280; font-size: 13px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .inline-actions { display: grid; gap: 8px; min-width: 260px; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 9px 13px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-weight: 700; }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        .btn.success { background: #166534; color: #fff; }
        .btn.warning { background: #92400e; color: #fff; }
        .btn.active { background: #92400e; color: #fff; }
        .btn.small { padding: 6px 10px; font-size: 12px; }
        input, select, textarea { border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; min-width: 180px; box-sizing: border-box; }
        textarea { width: 100%; min-height: 60px; resize: vertical; }
        .stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .stat { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; }
        .stat-value { font-size: 24px; font-weight: 800; }
        .stat-label { color: #6b7280; font-size: 13px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; }
        .badge.customer { background: #dbeafe; color: #1e40af; }
        .badge.supplier { background: #dcfce7; color: #166534; }
        .badge.completed { background: #dcfce7; color: #166534; }
        .badge.open { background: #fee2e2; color: #991b1b; }
        .summary { max-width: 330px; white-space: pre-wrap; }
        .result { color: #166534; margin-top: 8px; white-space: pre-wrap; }
        .pagination { margin-top: 14px; }
        @media (max-width: 900px) {
            .stats { grid-template-columns: 1fr; }
            table { display: block; overflow-x: auto; white-space: nowrap; }
            input, select, textarea { width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>مركز المتابعات</h1>
        <div class="muted">كل مواعيد المتابعة للعملاء والموردين من سجلات التواصل في مكان واحد.</div>

        @if(session('success'))
            <div class="card" style="background:#f0fdf4; border:1px solid #bbf7d0; box-shadow:none;" data-testid="follow-ups-success-message">
                {{ session('success') }}
            </div>
        @endif

        <div class="stats">
            <div class="stat" data-testid="follow-ups-due-count">
                <div class="stat-value">{{ $dueCount }}</div>
                <div class="stat-label">مستحقة أو متأخرة</div>
            </div>

            <div class="stat" data-testid="follow-ups-upcoming-count">
                <div class="stat-value">{{ $upcomingCount }}</div>
                <div class="stat-label">قادمة</div>
            </div>

            <div class="stat" data-testid="follow-ups-completed-count">
                <div class="stat-value">{{ $completedCount }}</div>
                <div class="stat-label">مكتملة</div>
            </div>

            <div class="stat" data-testid="follow-ups-all-count">
                <div class="stat-value">{{ $allCount }}</div>
                <div class="stat-label">كل المتابعات</div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('party-follow-ups.index', ['status' => 'due', 'q' => $search]) }}" class="btn {{ $status === 'due' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-due">المستحقة</a>
            <a href="{{ route('party-follow-ups.index', ['status' => 'upcoming', 'q' => $search]) }}" class="btn {{ $status === 'upcoming' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-upcoming">القادمة</a>
            <a href="{{ route('party-follow-ups.index', ['status' => 'completed', 'q' => $search]) }}" class="btn {{ $status === 'completed' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-completed">المكتملة</a>
            <a href="{{ route('party-follow-ups.index', ['status' => 'all', 'q' => $search]) }}" class="btn {{ $status === 'all' ? 'active' : 'secondary' }}" data-testid="follow-ups-filter-all">الكل</a>
        </div>

        <form method="GET" action="{{ route('party-follow-ups.index') }}" class="actions" data-testid="follow-ups-search-form">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ $search }}" placeholder="بحث بالاسم أو الملخص أو نتيجة المتابعة" data-testid="follow-ups-search-input">
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
                    <th>حالة المتابعة</th>
                    <th>تاريخ التواصل</th>
                    <th>تاريخ المتابعة</th>
                    <th>الملخص</th>
                    <th>الرابط</th>
                    <th>الإجراء</th>
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

                        $isCompleted = ! is_null($followUp->follow_up_completed_at);
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
                        <td>
                            @if($isCompleted)
                                <span class="badge completed" data-testid="follow-up-status-completed-{{ $followUp->id }}">مكتملة</span>
                                <div class="muted">{{ $followUp->follow_up_completed_at?->format('Y-m-d H:i') }}</div>
                            @else
                                <span class="badge open" data-testid="follow-up-status-open-{{ $followUp->id }}">مفتوحة</span>
                            @endif
                        </td>
                        <td>{{ $followUp->contacted_at?->format('Y-m-d') ?: '-' }}</td>
                        <td><span class="badge">{{ $followUp->follow_up_at?->format('Y-m-d') ?: '-' }}</span></td>
                        <td class="summary">
                            {{ $followUp->summary }}

                            @if($followUp->follow_up_result)
                                <div class="result" data-testid="follow-up-result-{{ $followUp->id }}">
                                    نتيجة المتابعة: {{ $followUp->follow_up_result }}
                                </div>
                            @endif
                        </td>
                        <td><a class="btn small secondary" href="{{ $showRoute }}" data-testid="follow-up-open-{{ $followUp->id }}">فتح السجل</a></td>
                        <td>
                            <div class="inline-actions">
                                @unless($isCompleted)
                                    <form method="POST" action="{{ route('party-follow-ups.complete', $followUp) }}" data-testid="follow-up-complete-form-{{ $followUp->id }}">
                                        @csrf
                                        <textarea name="follow_up_result" placeholder="نتيجة المتابعة"></textarea>
                                        <button type="submit" class="btn small success" data-testid="follow-up-complete-button-{{ $followUp->id }}">تمت المتابعة</button>
                                    </form>
                                @endunless

                                <form method="POST" action="{{ route('party-follow-ups.reschedule', $followUp) }}" data-testid="follow-up-reschedule-form-{{ $followUp->id }}">
                                    @csrf
                                    <input type="date" name="follow_up_at" required>
                                    <textarea name="follow_up_result" placeholder="سبب التأجيل / ملاحظة"></textarea>
                                    <button type="submit" class="btn small warning" data-testid="follow-up-reschedule-button-{{ $followUp->id }}">تأجيل</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="muted" data-testid="follow-ups-empty">لا توجد متابعات مطابقة.</td>
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
