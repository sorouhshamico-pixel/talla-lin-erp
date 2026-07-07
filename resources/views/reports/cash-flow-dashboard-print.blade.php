<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>طباعة لوحة التدفق النقدي المتوقع</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        @media print {
            .actions {
                display: none;
            }

            body {
                margin: 0;
            }

            .summary {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button type="button" class="button" onclick="window.print()" data-testid="cash-flow-print-button">طباعة</button>
    </div>

    <div class="header">
        <h1>لوحة التدفق النقدي المتوقع</h1>
        <div class="meta">تاريخ التقرير: {{ $reportDate->format('Y-m-d') }}</div>
    </div>

    <h2>ملخص التدفقات الداخلة</h2>
    <div class="summary" data-testid="cash-flow-print-inflow-summary">
        <div class="card">
            <div class="label">عدد العملاء أصحاب الذمم</div>
            <div class="value">{{ $inflowSummary['customers_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">فواتير العملاء المفتوحة</div>
            <div class="value">{{ $inflowSummary['open_invoice_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">التدفقات الداخلة المتوقعة</div>
            <div class="value">{{ number_format((float) $inflowSummary['expected_inflows'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">تدفقات داخلة متأخرة</div>
            <div class="value">{{ number_format((float) $inflowSummary['overdue_inflows'], 2) }}</div>
        </div>
    </div>

    <h2>ملخص التدفقات الخارجة</h2>
    <div class="summary" data-testid="cash-flow-print-outflow-summary">
        <div class="card">
            <div class="label">عدد الموردين أصحاب الذمم</div>
            <div class="value">{{ $outflowSummary['suppliers_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">فواتير الموردين المفتوحة</div>
            <div class="value">{{ $outflowSummary['open_invoice_count'] }}</div>
        </div>
        <div class="card">
            <div class="label">التدفقات الخارجة المتوقعة</div>
            <div class="value">{{ number_format((float) $outflowSummary['expected_outflows'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">تدفقات خارجة متأخرة</div>
            <div class="value">{{ number_format((float) $outflowSummary['overdue_outflows'], 2) }}</div>
        </div>
    </div>

    <h2>صافي التدفق النقدي</h2>
    <div class="summary" data-testid="cash-flow-print-net-summary">
        <div class="card">
            <div class="label">صافي التدفق النقدي المتوقع</div>
            <div class="value">{{ number_format((float) $netCashSummary['net_expected_cash'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">حالة التدفق النقدي المتوقع</div>
            <div class="value">{{ $netCashSummary['position_label'] }}</div>
        </div>
    </div>

    <h2>مخاطر التدفق النقدي</h2>
    <div class="summary" data-testid="cash-flow-print-risk-summary">
        <div class="card">
            <div class="label">إجمالي التدفقات الداخلة المتأخرة</div>
            <div class="value">{{ number_format((float) $riskSummary['overdue_inflows'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">إجمالي التدفقات الخارجة المتأخرة</div>
            <div class="value">{{ number_format((float) $riskSummary['overdue_outflows'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">صافي الضغط النقدي المتأخر</div>
            <div class="value">{{ number_format((float) $riskSummary['net_overdue_pressure'], 2) }}</div>
        </div>
        <div class="card">
            <div class="label">حالة الضغط النقدي</div>
            <div class="value">{{ $riskSummary['pressure_label'] }}</div>
        </div>
        <div class="card">
            <div class="label">نسبة تغطية الالتزامات المتوقعة</div>
            <div class="value">{{ $riskSummary['cash_coverage_ratio'] === null ? 'غير مطبق' : number_format((float) $riskSummary['cash_coverage_ratio'], 2) . '%' }}</div>
        </div>
        <div class="card">
            <div class="label">حالة التغطية النقدية</div>
            <div class="value">{{ $riskSummary['coverage_label'] }}</div>
        </div>
    </div>

    <h2>التدفق النقدي حسب شرائح الأعمار</h2>
    <table data-testid="cash-flow-print-bucket-comparison">
        <thead>
            <tr>
                <th>شريحة العمر</th>
                <th>تدفقات داخلة متوقعة</th>
                <th>تدفقات خارجة متوقعة</th>
                <th>صافي التدفق النقدي</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($bucketCashFlow as $bucket)
                <tr>
                    <td>{{ $bucket['label'] }}</td>
                    <td>{{ number_format((float) $bucket['expected_inflows'], 2) }}</td>
                    <td>{{ number_format((float) $bucket['expected_outflows'], 2) }}</td>
                    <td>{{ number_format((float) $bucket['net_cash_flow'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
