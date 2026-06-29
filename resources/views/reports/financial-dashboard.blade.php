<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>الداشبورد المالية</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #f6f7fb;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
        }

        .metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .metric {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 18px;
            background: #fafafa;
        }

        .metric-label {
            color: #6b7280;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .metric-value {
            font-size: 26px;
            font-weight: 800;
        }

        .green {
            color: #047857;
        }

        .red {
            color: #b91c1c;
        }

        .gold {
            color: #92400e;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 11px 16px;
            background: #111827;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn.secondary {
            background: #374151;
        }

        @media (max-width: 900px) {
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container" data-testid="financial-dashboard">
    <div class="card">
        <h1>الداشبورد المالية</h1>
        <div class="muted">
            ملخص مالي سريع للفترة الحالية من {{ $fromDate }} إلى {{ $toDate }} مع مؤشرات التحصيل والسداد.
        </div>
    </div>

    <div class="metrics">
        <div class="metric" data-testid="financial-dashboard-current-month-revenues">
            <div class="metric-label">إجمالي الإيرادات هذا الشهر</div>
            <div class="metric-value green">{{ number_format((float) $currentMonthRevenues, 2) }} ريال</div>
        </div>

        <div class="metric" data-testid="financial-dashboard-current-month-expenses">
            <div class="metric-label">إجمالي المصروفات هذا الشهر</div>
            <div class="metric-value red">{{ number_format((float) $currentMonthExpenses, 2) }} ريال</div>
        </div>

        <div class="metric" data-testid="financial-dashboard-current-month-net-profit">
            <div class="metric-label">صافي الربح هذا الشهر</div>
            <div class="metric-value {{ $currentMonthNetProfit >= 0 ? 'green' : 'red' }}">
                {{ number_format((float) $currentMonthNetProfit, 2) }} ريال
            </div>
        </div>

        <div class="metric" data-testid="financial-dashboard-uncollected-revenues">
            <div class="metric-label">الإيرادات غير المحصلة</div>
            <div class="metric-value gold">{{ number_format((float) $uncollectedRevenues, 2) }} ريال</div>
        </div>

        <div class="metric" data-testid="financial-dashboard-unpaid-expenses">
            <div class="metric-label">المصروفات غير المدفوعة</div>
            <div class="metric-value gold">{{ number_format((float) $unpaidExpenses, 2) }} ريال</div>
        </div>

        <div class="metric" data-testid="financial-dashboard-profit-loss-link-card">
            <div class="metric-label">تقرير تفصيلي</div>
            <div class="metric-value" style="font-size: 18px; margin-bottom: 12px;">
                الأرباح والخسائر
            </div>

            <div class="actions">
                <a
                    href="{{ route('reports.profit-loss') }}"
                    class="btn"
                    data-testid="financial-dashboard-profit-loss-link"
                >
                    فتح التقرير
                </a>

                <a
                    href="{{ route('reports.profit-loss.export') }}"
                    class="btn secondary"
                    data-testid="financial-dashboard-profit-loss-export-link"
                >
                    تصدير CSV
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
