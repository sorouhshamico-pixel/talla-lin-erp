<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة الملخص المالي السريع</title>
    <style>
        body {
            font-family: Tahoma, Arial, sans-serif;
            color: #111827;
            background: #ffffff;
            margin: 24px;
            line-height: 1.6;
            font-size: 13px;
        }

        .actions {
            margin-bottom: 20px;
        }

        .button {
            border: 1px solid #111827;
            border-radius: 6px;
            padding: 8px 14px;
            background: #ffffff;
            color: #111827;
            cursor: pointer;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 22px;
        }

        h2 {
            margin: 18px 0 10px;
            font-size: 16px;
        }

        .meta {
            color: #4b5563;
            margin-top: 3px;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 10px;
        }

        .label {
            color: #6b7280;
            font-size: 12px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="button" onclick="window.print()" data-testid="main-dashboard-financial-print-button">طباعة</button>
    </div>

    <div class="header">
        <h1>الملخص المالي السريع للوحة التحكم</h1>
        <div class="meta">تاريخ التقرير: {{ $reportDate->format('Y-m-d H:i:s') }}</div>
    </div>

    <h2>ذمم العملاء</h2>
    <div class="summary" data-testid="main-dashboard-financial-print-customer-summary">
        <div class="card">
            <div class="label">عدد العملاء أصحاب الذمم</div>
            <div class="value">{{ $summary['customers_count'] }}</div>
        </div>

        <div class="card">
            <div class="label">فواتير العملاء المفتوحة</div>
            <div class="value">{{ $summary['customer_open_invoice_count'] }}</div>
        </div>

        <div class="card">
            <div class="label">ذمم العملاء المفتوحة</div>
            <div class="value">{{ number_format((float) $summary['expected_inflows'], 2) }} ريال</div>
        </div>

        <div class="card">
            <div class="label">متأخرات العملاء</div>
            <div class="value">{{ number_format((float) $summary['overdue_inflows'], 2) }} ريال</div>
        </div>
    </div>

    <h2>التزامات الموردين</h2>
    <div class="summary" data-testid="main-dashboard-financial-print-supplier-summary">
        <div class="card">
            <div class="label">عدد الموردين أصحاب الذمم</div>
            <div class="value">{{ $summary['suppliers_count'] }}</div>
        </div>

        <div class="card">
            <div class="label">فواتير الموردين المفتوحة</div>
            <div class="value">{{ $summary['supplier_open_invoice_count'] }}</div>
        </div>

        <div class="card">
            <div class="label">التزامات الموردين المفتوحة</div>
            <div class="value">{{ number_format((float) $summary['expected_outflows'], 2) }} ريال</div>
        </div>

        <div class="card">
            <div class="label">متأخرات الموردين</div>
            <div class="value">{{ number_format((float) $summary['overdue_outflows'], 2) }} ريال</div>
        </div>
    </div>

    <h2>التدفق النقدي المتوقع</h2>
    <div class="summary" data-testid="main-dashboard-financial-print-cash-flow-summary">
        <div class="card">
            <div class="label">صافي التدفق النقدي المتوقع</div>
            <div class="value">{{ number_format((float) $summary['net_expected_cash'], 2) }} ريال</div>
        </div>

        <div class="card">
            <div class="label">حالة التدفق النقدي</div>
            <div class="value">{{ $summary['position_label'] }}</div>
        </div>
    </div>

    <h2>مؤشرات المخاطر المالية</h2>
    <div class="summary" data-testid="main-dashboard-financial-print-risk-summary">
        <div class="card">
            <div class="label">صافي الضغط النقدي المتأخر</div>
            <div class="value">{{ number_format((float) $summary['net_overdue_pressure'], 2) }} ريال</div>
        </div>

        <div class="card">
            <div class="label">نسبة تغطية الالتزامات</div>
            <div class="value">{{ $summary['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $summary['cash_coverage_ratio'], 2) . '%' }}</div>
        </div>

        <div class="card">
            <div class="label">حالة التغطية النقدية</div>
            <div class="value">{{ $summary['cash_coverage_label'] }}</div>
        </div>

        <div class="card">
            <div class="label">مؤشر المتابعة المالية</div>
            <div class="value">{{ $summary['risk_label'] }}</div>
        </div>
    </div>
</body>
</html>
