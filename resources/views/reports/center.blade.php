<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>مركز التقارير</title>
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

        .header,
        .report-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, .05);
        }

        .header {
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 28px;
        }

        h2 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .muted {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.8;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
        }

        .report-card {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 180px;
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
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
            font-size: 14px;
        }

        .btn.secondary {
            background: #374151;
        }

        .badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            border-radius: 999px;
            padding: 5px 10px;
            font-size: 12px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container" data-testid="reports-center">
    <div class="header">
        <h1>مركز التقارير</h1>
        <div class="muted">
            صفحة مركزية للوصول السريع إلى التقارير المالية ولوحات المتابعة.
        </div>
    </div>

    <div class="grid">
        <div class="report-card" data-testid="reports-center-financial-dashboard-card">
            <div>
                <span class="badge">ملخص سريع</span>
                <h2>الداشبورد المالية</h2>
                <div class="muted">
                    مؤشرات هذا الشهر، الإيرادات، المصروفات، صافي الربح، والمبالغ غير المحصلة أو غير المدفوعة.
                </div>
            </div>

            <div class="actions">
                <a
                    href="{{ route('reports.financial-dashboard') }}"
                    class="btn"
                    data-testid="reports-center-financial-dashboard-link"
                >
                    فتح الداشبورد
                </a>
            </div>
        </div>

        <div class="report-card" data-testid="reports-center-profit-loss-card">
            <div>
                <span class="badge">تقرير تفصيلي</span>
                <h2>تقرير الأرباح والخسائر</h2>
                <div class="muted">
                    إجمالي الإيرادات والمصروفات وصافي الربح مع فلاتر التاريخ والفرع والملخص الشهري.
                </div>
            </div>

            <div class="actions">
                <a
                    href="{{ route('reports.profit-loss') }}"
                    class="btn"
                    data-testid="reports-center-profit-loss-link"
                >
                    فتح التقرير
                </a>
            </div>
        </div>

        <div class="report-card" data-testid="reports-center-profit-loss-export-card">
            <div>
                <span class="badge">CSV</span>
                <h2>تصدير الأرباح والخسائر</h2>
                <div class="muted">
                    تحميل ملخص الأرباح والخسائر بصيغة CSV لاستخدامه في Excel أو الأرشفة.
                </div>
            </div>

            <div class="actions">
                <a
                    href="{{ route('reports.profit-loss.export') }}"
                    class="btn secondary"
                    data-testid="reports-center-profit-loss-export-link"
                >
                    تصدير CSV
                </a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
