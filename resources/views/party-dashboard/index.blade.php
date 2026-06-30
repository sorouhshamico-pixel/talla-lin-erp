<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة العملاء والموردين</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; background: #f4f6f8; color: #111827; padding: 24px; }
        .container { max-width: 1240px; margin: 0 auto; }
        .card { background: #fff; border-radius: 14px; padding: 18px; box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08); margin-bottom: 18px; }
        h1 { margin: 0 0 8px; font-size: 26px; }
        h2 { margin: 0 0 10px; font-size: 20px; }
        .muted { color: #6b7280; font-size: 13px; }
        .stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 16px; }
        .stat { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 16px; }
        .stat-value { font-size: 28px; font-weight: 800; }
        .stat-label { color: #6b7280; font-size: 13px; margin-top: 6px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 14px; }
        .btn { display: inline-block; background: #111827; color: #fff; padding: 10px 14px; border-radius: 10px; text-decoration: none; border: 0; cursor: pointer; font-weight: 700; }
        .btn.secondary { background: #e5e7eb; color: #111827; }
        .quick-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-top: 14px; }
        .quick-link { border: 1px solid #e5e7eb; background: #fafafa; border-radius: 12px; padding: 14px; text-decoration: none; color: #111827; display: block; }
        .quick-link strong { display: block; margin-bottom: 6px; }
        @media (max-width: 900px) {
            .stats, .quick-grid { grid-template-columns: 1fr; }
            body { padding: 14px; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>لوحة العملاء والموردين</h1>
        <div class="muted">ملخص تنفيذي سريع لوحدة العملاء والموردين والمتابعات والتصنيفات والتكرارات.</div>

        <div class="actions">
            <a href="{{ route('customers.index') }}" class="btn secondary" data-testid="party-dashboard-customers-link">العملاء</a>
            <a href="{{ route('suppliers.index') }}" class="btn secondary" data-testid="party-dashboard-suppliers-link">الموردون</a>
            <a href="{{ route('party-follow-ups.index') }}" class="btn secondary" data-testid="party-dashboard-follow-ups-link">مركز المتابعات</a>
        </div>
    </div>

    <div class="card" data-testid="party-dashboard-stats-card">
        <h2>المؤشرات الرئيسية</h2>

        <div class="stats">
            <div class="stat" data-testid="party-dashboard-customers-total-card">
                <div class="stat-value" data-testid="party-dashboard-customers-total">{{ $summary['customers_total'] }}</div>
                <div class="stat-label">إجمالي العملاء</div>
            </div>

            <div class="stat" data-testid="party-dashboard-customers-active-card">
                <div class="stat-value" data-testid="party-dashboard-customers-active">{{ $summary['customers_active'] }}</div>
                <div class="stat-label">العملاء النشطون</div>
            </div>

            <div class="stat" data-testid="party-dashboard-suppliers-total-card">
                <div class="stat-value" data-testid="party-dashboard-suppliers-total">{{ $summary['suppliers_total'] }}</div>
                <div class="stat-label">إجمالي الموردين</div>
            </div>

            <div class="stat" data-testid="party-dashboard-suppliers-active-card">
                <div class="stat-value" data-testid="party-dashboard-suppliers-active">{{ $summary['suppliers_active'] }}</div>
                <div class="stat-label">الموردون النشطون</div>
            </div>

            <div class="stat" data-testid="party-dashboard-follow-ups-due-card">
                <div class="stat-value" data-testid="party-dashboard-follow-ups-due">{{ $summary['follow_ups_due'] }}</div>
                <div class="stat-label">متابعات مستحقة</div>
            </div>

            <div class="stat" data-testid="party-dashboard-follow-ups-upcoming-card">
                <div class="stat-value" data-testid="party-dashboard-follow-ups-upcoming">{{ $summary['follow_ups_upcoming'] }}</div>
                <div class="stat-label">متابعات قادمة</div>
            </div>

            <div class="stat" data-testid="party-dashboard-follow-ups-completed-card">
                <div class="stat-value" data-testid="party-dashboard-follow-ups-completed">{{ $summary['follow_ups_completed'] }}</div>
                <div class="stat-label">متابعات مكتملة</div>
            </div>

            <div class="stat" data-testid="party-dashboard-tags-total-card">
                <div class="stat-value" data-testid="party-dashboard-tags-total">{{ $summary['party_tags_total'] }}</div>
                <div class="stat-label">عدد التصنيفات</div>
            </div>

            <div class="stat" data-testid="party-dashboard-duplicates-total-card">
                <div class="stat-value" data-testid="party-dashboard-duplicates-total">{{ $summary['duplicate_groups_total'] }}</div>
                <div class="stat-label">مجموعات التكرار</div>
            </div>
        </div>
    </div>

    <div class="card" data-testid="party-dashboard-quick-links-card">
        <h2>روابط سريعة</h2>

        <div class="quick-grid">
            <a href="{{ route('customers.index') }}" class="quick-link" data-testid="party-dashboard-quick-customers">
                <strong>العملاء</strong>
                <span class="muted">عرض وإدارة سجلات العملاء.</span>
            </a>

            <a href="{{ route('suppliers.index') }}" class="quick-link" data-testid="party-dashboard-quick-suppliers">
                <strong>الموردون</strong>
                <span class="muted">عرض وإدارة سجلات الموردين.</span>
            </a>

            <a href="{{ route('party-follow-ups.index') }}" class="quick-link" data-testid="party-dashboard-quick-follow-ups">
                <strong>مركز المتابعات</strong>
                <span class="muted">متابعة العملاء والموردين.</span>
            </a>

            <a href="{{ route('party-tags.index') }}" class="quick-link" data-testid="party-dashboard-quick-tags">
                <strong>التصنيفات</strong>
                <span class="muted">إدارة تصنيفات العملاء والموردين.</span>
            </a>

            <a href="{{ route('party-duplicates.index') }}" class="quick-link" data-testid="party-dashboard-quick-duplicates">
                <strong>كشف التكرارات</strong>
                <span class="muted">مراجعة التكرارات حسب الهاتف والبريد.</span>
            </a>

            <a href="{{ route('party-permissions.index') }}" class="quick-link" data-testid="party-dashboard-quick-permissions">
                <strong>صلاحيات الوصول</strong>
                <span class="muted">مراجعة صلاحيات وحدة العملاء والموردين.</span>
            </a>
        </div>
    </div>
</div>
</body>
</html>
