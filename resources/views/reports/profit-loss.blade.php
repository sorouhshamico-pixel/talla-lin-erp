<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تقرير الأرباح والخسائر</title>
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

        .filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            align-items: end;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: 14px;
        }

        input,
        select {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 10px;
            box-sizing: border-box;
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

        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .metric {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            background: #fafafa;
        }

        .metric-label {
            color: #6b7280;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 800;
        }

        .green {
            color: #047857;
        }

        .red {
            color: #b91c1c;
        }

        @media (max-width: 900px) {
            .filters,
            .metrics {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container" data-testid="profit-loss-report">
    <div class="card">
        <h1>تقرير الأرباح والخسائر</h1>
        <div class="muted">
            يعرض التقرير ملخص الإيرادات والمصروفات وصافي الربح حسب الفترة والفرع.
        </div>
    </div>

    <div class="card">
        <form method="GET" action="{{ route('reports.profit-loss') }}" class="filters" data-testid="profit-loss-filter-form">
            <div>
                <label for="from_date">من تاريخ</label>
                <input id="from_date" type="date" name="from_date" value="{{ $filters['from_date'] }}">
            </div>

            <div>
                <label for="to_date">إلى تاريخ</label>
                <input id="to_date" type="date" name="to_date" value="{{ $filters['to_date'] }}">
            </div>

            <div>
                <label for="branch_id">الفرع</label>
                <select id="branch_id" name="branch_id">
                    <option value="">كل الفروع</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) $filters['branch_id'] === (string) $branch->id)>
                            {{ $branch->name_ar ?? $branch->name ?? $branch->name_en ?? ('فرع #' . $branch->id) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="btn">تطبيق الفلاتر</button>
            </div>
        </form>
    </div>

    <div class="metrics">
        <div class="metric" data-testid="profit-loss-total-revenues">
            <div class="metric-label">إجمالي الإيرادات</div>
            <div class="metric-value green">{{ number_format((float) $totalRevenues, 2) }} ريال</div>
        </div>

        <div class="metric" data-testid="profit-loss-total-expenses">
            <div class="metric-label">إجمالي المصروفات</div>
            <div class="metric-value red">{{ number_format((float) $totalExpenses, 2) }} ريال</div>
        </div>

        <div class="metric" data-testid="profit-loss-net-profit">
            <div class="metric-label">صافي الربح / الخسارة</div>
            <div class="metric-value {{ $netProfit >= 0 ? 'green' : 'red' }}">
                {{ number_format((float) $netProfit, 2) }} ريال
            </div>
        </div>

        <div class="metric" data-testid="profit-loss-tax-difference">
            <div class="metric-label">فرق الضريبة</div>
            <div class="metric-value">
                {{ number_format((float) $taxDifference, 2) }} ريال
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0;">تفاصيل الضريبة</h2>

        <div class="metrics">
            <div class="metric" data-testid="profit-loss-revenue-tax">
                <div class="metric-label">ضريبة الإيرادات</div>
                <div class="metric-value">{{ number_format((float) $totalRevenueTax, 2) }} ريال</div>
            </div>

            <div class="metric" data-testid="profit-loss-expense-tax">
                <div class="metric-label">ضريبة المصروفات</div>
                <div class="metric-value">{{ number_format((float) $totalExpenseTax, 2) }} ريال</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
