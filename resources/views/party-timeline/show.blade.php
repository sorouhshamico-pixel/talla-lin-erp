<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }} - {{ $party->name }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 980px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        h2 { margin: 0 0 10px; font-size: 20px; }
        .muted { color: #6b7280; font-size: 13px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 9px 13px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-weight: 700; }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        .summary-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .summary-box { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; }
        .summary-value { font-size: 24px; font-weight: 800; }
        .summary-label { color: #6b7280; font-size: 13px; margin-top: 4px; }
        .timeline { position: relative; margin-top: 8px; }
        .timeline-item { border: 1px solid #e5e7eb; border-radius: 14px; padding: 14px; margin-bottom: 12px; background: #fafafa; }
        .timeline-head { display: flex; justify-content: space-between; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
        .timeline-title { font-weight: 800; }
        .timeline-description { white-space: pre-wrap; line-height: 1.8; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #fef3c7; color: #92400e; font-size: 12px; font-weight: 700; }
        .badge.note { background: #dbeafe; color: #1e40af; }
        .badge.attachment { background: #dcfce7; color: #166534; }
        .badge.contact_log { background: #fef3c7; color: #92400e; }
        @media (max-width: 800px) {
            .summary-grid { grid-template-columns: 1fr; }
            body { padding: 14px; }
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

        <div class="summary-grid">
            <div class="summary-box" data-testid="timeline-total-count">
                <div class="summary-value">{{ $timeline->count() }}</div>
                <div class="summary-label">إجمالي الأنشطة</div>
            </div>

            <div class="summary-box" data-testid="timeline-party-type">
                <div class="summary-value">{{ $partyTypeLabel }}</div>
                <div class="summary-label">نوع السجل</div>
            </div>

            <div class="summary-box" data-testid="timeline-party-name">
                <div class="summary-value" style="font-size: 18px;">{{ $party->name }}</div>
                <div class="summary-label">اسم السجل</div>
            </div>
        </div>

        <div class="actions">
            <a href="{{ $partyShowRoute }}" class="btn secondary" data-testid="timeline-back-link">العودة إلى صفحة التفاصيل</a>
        </div>
    </div>

    <div class="card" data-testid="activity-timeline-card">
        <h2>خط النشاط</h2>
        <div class="muted">يعرض أحدث الملاحظات والمرفقات وسجلات التواصل مرتبطة بهذا السجل.</div>

        <div class="timeline" data-testid="activity-timeline-list">
            @forelse($timeline as $index => $item)
                <div class="timeline-item" data-testid="activity-timeline-item-{{ $index }}">
                    <div class="timeline-head">
                        <div>
                            <span class="badge {{ $item['type'] }}">{{ $item['type_label'] }}</span>
                            <span class="timeline-title">{{ $item['title'] }}</span>
                        </div>
                        <div class="muted">{{ $item['occurred_at'] ? \Illuminate\Support\Carbon::parse($item['occurred_at'])->format('Y-m-d H:i') : '-' }}</div>
                    </div>

                    <div class="timeline-description">{{ $item['description'] }}</div>
                    <div class="muted" style="margin-top: 8px;">{{ $item['meta'] }}</div>
                </div>
            @empty
                <div class="muted" data-testid="activity-timeline-empty">لا توجد أنشطة مسجلة بعد.</div>
            @endforelse
        </div>
    </div>
</div>
</body>
</html>
