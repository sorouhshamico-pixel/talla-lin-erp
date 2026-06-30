<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تصنيفات العملاء والموردين</title>
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
        .btn.danger { background: #991b1b; color: #fff; }
        input, select, textarea { border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; min-width: 180px; box-sizing: border-box; }
        textarea { min-height: 70px; min-width: 320px; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { padding: 11px 10px; border-bottom: 1px solid #e5e7eb; text-align: right; vertical-align: top; }
        th { background: #f9fafb; font-size: 13px; color: #374151; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; }
        .badge.active { background: #dcfce7; color: #166534; }
        .badge.inactive { background: #fee2e2; color: #991b1b; }
        @media (max-width: 800px) {
            table { display: block; overflow-x: auto; white-space: nowrap; }
            input, select, textarea { width: 100%; min-width: 0; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>تصنيفات العملاء والموردين</h1>
        <div class="muted">إدارة تصنيفات تساعدك على تمييز العملاء والموردين وتنظيم المتابعة.</div>

        @if(session('success'))
            <div class="card" style="background:#f0fdf4; border:1px solid #bbf7d0; box-shadow:none;" data-testid="party-tags-success-message">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="card" style="background:#fef2f2; border:1px solid #fecaca; box-shadow:none;" data-testid="party-tags-error-message">
                {{ session('error') }}
            </div>
        @endif
    </div>

    <div class="card">
        <h2>إضافة تصنيف جديد</h2>

        <form method="POST" action="{{ route('party-tags.store') }}" class="actions" data-testid="party-tags-create-form">
            @csrf

            <input type="text" name="name" placeholder="اسم التصنيف" required data-testid="party-tags-name-input">

            <select name="applies_to" required data-testid="party-tags-applies-to-select">
                <option value="both">عملاء وموردين</option>
                <option value="customer">عملاء فقط</option>
                <option value="supplier">موردين فقط</option>
            </select>

            <textarea name="description" placeholder="وصف اختياري" data-testid="party-tags-description-input"></textarea>

            <label>
                <input type="checkbox" name="is_active" value="1" checked>
                نشط
            </label>

            <button type="submit" class="btn" data-testid="party-tags-create-submit">حفظ التصنيف</button>
        </form>
    </div>

    <div class="card">
        <h2>قائمة التصنيفات</h2>

        <table data-testid="party-tags-table">
            <thead>
                <tr>
                    <th>التصنيف</th>
                    <th>النطاق</th>
                    <th>الحالة</th>
                    <th>العملاء</th>
                    <th>الموردون</th>
                    <th>الوصف</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($partyTags as $partyTag)
                    <tr data-testid="party-tag-row-{{ $partyTag->id }}">
                        <td><strong>{{ $partyTag->name }}</strong></td>
                        <td>
                            @if($partyTag->applies_to === 'customer')
                                <span class="badge">عملاء فقط</span>
                            @elseif($partyTag->applies_to === 'supplier')
                                <span class="badge">موردين فقط</span>
                            @else
                                <span class="badge">عملاء وموردين</span>
                            @endif
                        </td>
                        <td>
                            @if($partyTag->is_active)
                                <span class="badge active">نشط</span>
                            @else
                                <span class="badge inactive">غير نشط</span>
                            @endif
                        </td>
                        <td>{{ $partyTag->customers_count }}</td>
                        <td>{{ $partyTag->suppliers_count }}</td>
                        <td>{{ $partyTag->description ?: '-' }}</td>
                        <td>
                            <div class="actions" style="margin-top:0;">
                                <a href="{{ route('party-tags.show', $partyTag) }}" class="btn secondary" data-testid="party-tag-show-{{ $partyTag->id }}">عرض المرتبطين</a>

                                <form method="POST" action="{{ route('party-tags.toggle-active', $partyTag) }}">
                                    @csrf
                                    <button type="submit" class="btn secondary" data-testid="party-tag-toggle-{{ $partyTag->id }}">تغيير الحالة</button>
                                </form>

                                <form method="POST" action="{{ route('party-tags.destroy', $partyTag) }}" onsubmit="return confirm('هل تريد حذف هذا التصنيف؟');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn danger" data-testid="party-tag-delete-{{ $partyTag->id }}">حذف</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="muted" data-testid="party-tags-empty">لا توجد تصنيفات بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
